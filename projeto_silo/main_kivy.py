# main_kivy.py
import os
import sqlite3
import argparse
from datetime import datetime
from reportlab.pdfgen import canvas
from reportlab.lib.pagesizes import A6
from flask import Flask, request, jsonify

# Importações do Kivy
from kivy.app import App
from kivy.lang import Builder
from kivy.uix.boxlayout import BoxLayout
from kivy.uix.popup import Popup
from kivy.uix.label import Label
from kivy.properties import ListProperty
from kivy.clock import Clock

DB_FILE = 'silos.db'
TICKETS_DIR = 'tickets'
os.makedirs(TICKETS_DIR, exist_ok=True)

# ... (todo o código de banco de dados e funções de negócio fica aqui, sem alteração) ...
def get_conn():
    conn = sqlite3.connect(DB_FILE)
    conn.row_factory = sqlite3.Row
    return conn

def init_db():
    conn = get_conn()
    cur = conn.cursor()
    cur.execute('''
        CREATE TABLE IF NOT EXISTS clients (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            doc TEXT
        )
    ''')
    cur.execute('''
        CREATE TABLE IF NOT EXISTS farms (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            client_id INTEGER NOT NULL,
            name TEXT NOT NULL,
            FOREIGN KEY(client_id) REFERENCES clients(id)
        )
    ''')
    cur.execute('''
        CREATE TABLE IF NOT EXISTS movements (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            type TEXT NOT NULL CHECK(type IN ('entry','exit')),
            client_id INTEGER NOT NULL,
            farm_id INTEGER,
            driver TEXT,
            vehicle TEXT,
            product TEXT,
            weight REAL,
            humidity REAL,
            impurity REAL,
            timestamp TEXT NOT NULL,
            FOREIGN KEY(client_id) REFERENCES clients(id),
            FOREIGN KEY(farm_id) REFERENCES farms(id)
        )
    ''')
    conn.commit()
    conn.close()

def add_client(name, doc=None):
    conn = get_conn()
    cur = conn.cursor()
    cur.execute('INSERT INTO clients (name, doc) VALUES (?,?)', (name, doc))
    conn.commit()
    conn.close()

def list_clients():
    conn = get_conn()
    cur = conn.cursor()
    cur.execute('SELECT * FROM clients ORDER BY name')
    rows = cur.fetchall()
    conn.close()
    return rows

def add_farm(client_id, name):
    conn = get_conn()
    cur = conn.cursor()
    cur.execute('INSERT INTO farms (client_id, name) VALUES (?,?)', (client_id, name))
    conn.commit()
    conn.close()

def list_farms(client_id=None):
    conn = get_conn()
    cur = conn.cursor()
    if client_id:
        cur.execute('SELECT * FROM farms WHERE client_id = ? ORDER BY name', (client_id,))
    else:
        cur.execute('SELECT * FROM farms ORDER BY name')
    rows = cur.fetchall()
    conn.close()
    return rows

def register_movement(mtype, client_id, farm_id, driver, vehicle, product, weight, humidity=None, impurity=None, timestamp=None):
    if timestamp is None:
        timestamp = datetime.now().isoformat()
    conn = get_conn()
    cur = conn.cursor()
    cur.execute('''
        INSERT INTO movements (type, client_id, farm_id, driver, vehicle, product, weight, humidity, impurity, timestamp)
        VALUES (?,?,?,?,?,?,?,?,?,?)
    ''', (mtype, client_id, farm_id, driver, vehicle, product, weight, humidity, impurity, timestamp))
    mov_id = cur.lastrowid
    conn.commit()
    conn.close()
    return mov_id

def list_movements(limit=200):
    conn = get_conn()
    cur = conn.cursor()
    cur.execute('SELECT m.*, c.name as client_name, f.name as farm_name FROM movements m JOIN clients c ON m.client_id = c.id LEFT JOIN farms f ON m.farm_id = f.id ORDER BY timestamp DESC LIMIT ?', (limit,))
    rows = cur.fetchall()
    conn.close()
    return rows

def compute_stock():
    conn = get_conn()
    cur = conn.cursor()
    cur.execute('SELECT product, type, SUM(weight) as total FROM movements GROUP BY product, type')
    rows = cur.fetchall()
    conn.close()
    products = {}
    for r in rows:
        prod = r['product'] or 'N/A'
        typ = r['type']
        total = r['total'] or 0
        if prod not in products:
            products[prod] = 0.0
        if typ == 'entry':
            products[prod] += total
        else:
            products[prod] -= total
    return products

def generate_ticket_pdf(mov_id):
    conn = get_conn()
    cur = conn.cursor()
    cur.execute('SELECT m.*, c.name as client_name, f.name as farm_name FROM movements m JOIN clients c ON m.client_id = c.id LEFT JOIN farms f ON m.farm_id = f.id WHERE m.id = ?', (mov_id,))
    r = cur.fetchone()
    conn.close()
    if not r:
        raise ValueError('Movimento não encontrado')
    tipo = 'DEPÓSITO' if r['type'] == 'entry' else 'RETIRADA'
    filename = os.path.join(TICKETS_DIR, f"ticket_{mov_id}_{r['timestamp'].replace(':','').replace('-','')}.pdf")
    c = canvas.Canvas(filename, pagesize=A6)
    width, height = A6
    y = height - 20
    c.setFont('Helvetica-Bold', 12)
    c.drawCentredString(width/2, y, 'SILO - ' + tipo)
    c.setFont('Helvetica', 9)
    y -= 20
    lines = [
        f"ID: {mov_id}",
        f"Cliente: {r['client_name']}",
        f"Fazenda: {r['farm_name'] or '-'}",
        f"Motorista: {r['driver'] or '-'}",
        f"Veículo: {r['vehicle'] or '-'}",
        f"Data/Hora: {r['timestamp']}",
        f"Produto: {r['product']}",
        f"Peso (kg): {r['weight']:.3f}",
        f"Umidade: {r['humidity'] if r['humidity'] is not None else '-'}",
        f"Impureza: {r['impurity'] if r['impurity'] is not None else '-'}",
    ]
    for line in lines:
        c.drawString(10, y, line)
        y -= 12
    c.showPage()
    c.save()
    return filename

# ---------- Classe principal da GUI Kivy ----------
class MainLayout(BoxLayout):
    pass

# ----> MUDANÇA 1: Renomeando a classe
class MainApp(App):
    client_list = ListProperty([])
    farm_list = ListProperty([''])

    def build(self):
        Builder.load_file('gestaosilos.kv')
        return MainLayout()

    def on_start(self):
        Clock.schedule_once(self.initial_refresh)

    def initial_refresh(self, dt=None):
        self.refresh_clients()
        self.refresh_movements()
        self.refresh_stock()

    def show_popup(self, title, message):
        popup = Popup(title=title,
                      content=Label(text=message, halign='center'),
                      size_hint=(None, None), size=(450, 200))
        popup.open()

    def on_add_client(self):
        name = self.root.ids.client_name_input.text.strip()
        doc = self.root.ids.client_doc_input.text.strip()
        if not name:
            self.show_popup('Aviso', 'Informe o nome do cliente')
            return
        add_client(name, doc or None)
        self.show_popup('Sucesso', 'Cliente adicionado')
        self.root.ids.client_name_input.text = ''
        self.root.ids.client_doc_input.text = ''
        self.refresh_clients()

    def on_add_farm(self):
        sel = self.root.ids.clients_combo.text
        if not sel or sel == 'Selecione um cliente':
            self.show_popup('Aviso', 'Selecione um cliente')
            return
        try:
            client_id = int(sel.split(' - ', 1)[0])
        except (ValueError, IndexError):
            self.show_popup('Aviso', 'Cliente inválido selecionado.')
            return
        name = self.root.ids.farm_name_input.text.strip()
        if not name:
            self.show_popup('Aviso', 'Informe o nome da fazenda')
            return
        add_farm(client_id, name)
        self.show_popup('Sucesso', 'Fazenda adicionada')
        self.root.ids.farm_name_input.text = ''
        self.refresh_clients()

    def on_register_movement(self):
        try:
            mtype = 'entry' if self.root.ids.check_entry.active else 'exit'
            client_sel = self.root.ids.mov_client_combo.text
            if not client_sel or client_sel == 'Selecione...':
                self.show_popup('Aviso', 'Selecione um cliente')
                return
            client_id = int(client_sel.split(' - ', 1)[0])
            farm_sel = self.root.ids.mov_farm_combo.text
            farm_id = int(farm_sel.split(' - ', 1)[0]) if farm_sel and farm_sel != 'Selecione...' else None
            driver = self.root.ids.mov_driver_input.text.strip()
            vehicle = self.root.ids.mov_vehicle_input.text.strip()
            product = self.root.ids.mov_product_input.text.strip() or 'N/A'
            weight = float(self.root.ids.mov_weight_input.text.strip())
            humidity_str = self.root.ids.mov_humidity_input.text.strip()
            humidity = float(humidity_str) if humidity_str else None
            impurity_str = self.root.ids.mov_impurity_input.text.strip()
            impurity = float(impurity_str) if impurity_str else None
        except Exception as e:
            self.show_popup('Erro', f'Confira os dados: {e}')
            return
        mov_id = register_movement(mtype, client_id, farm_id, driver or None, vehicle or None, product, weight, humidity, impurity)
        pdf = generate_ticket_pdf(mov_id)
        self.show_popup('Registrado', f'Movimento registrado.\nTicket gerado em:\n{pdf}')
        self.refresh_movements()
        self.refresh_stock()

    def refresh_clients(self):
        clients = list_clients()
        self.client_list = [f"{c['id']} - {c['name']}" for c in clients]
        self.root.ids.clients_combo.values = self.client_list
        self.root.ids.mov_client_combo.values = self.client_list
        farms_all = list_farms()
        self.farm_list = [''] + [f"{f['id']} - {f['name']}" for f in farms_all]
        self.root.ids.mov_farm_combo.values = self.farm_list
        self.on_client_selection_change(self.root.ids.clients_combo.text)

    def on_client_selection_change(self, selected_client):
        farms_rv = self.root.ids.farms_rv
        if not selected_client or selected_client == 'Selecione um cliente':
            farms_rv.data = []
            return
        try:
            client_id = int(selected_client.split(' - ', 1)[0])
            farms = list_farms(client_id)
            farms_rv.data = [{'text': f"{f['id']} - {f['name']}"} for f in farms]
        except (ValueError, IndexError):
            farms_rv.data = []

    def refresh_movements(self):
        rows = list_movements(500)
        log_rv = self.root.ids.log_rv
        log_rv.data = [{
            'id_text': str(r['id']), 'type_text': r['type'],
            'client_text': r['client_name'], 'farm_text': r['farm_name'] or '-',
            'product_text': r['product'], 'weight_text': f"{r['weight']:.3f}",
            'time_text': r['timestamp']
        } for r in rows]

    def refresh_stock(self):
        products = compute_stock()
        stock_rv = self.root.ids.stock_rv
        stock_rv.data = [{'product_text': prod, 'kg_text': f"{kg:.3f}"} for prod, kg in products.items()]

# ... (código da API fica aqui, sem alteração) ...
app = Flask(__name__)
@app.route('/clients', methods=['GET','POST'])
def api_clients():
    if request.method == 'POST':
        data = request.json
        if not data or 'name' not in data: return jsonify({'error':'name required'}), 400
        add_client(data['name'], data.get('doc'))
        return jsonify({'ok':True}), 201
    else:
        rows = list_clients()
        return jsonify([dict(r) for r in rows])
@app.route('/farms', methods=['GET','POST'])
def api_farms():
    if request.method == 'POST':
        data = request.json
        if not data or 'client_id' not in data or 'name' not in data: return jsonify({'error':'client_id and name required'}), 400
        add_farm(data['client_id'], data['name'])
        return jsonify({'ok':True}), 201
    else:
        client_id = request.args.get('client_id')
        rows = list_farms(int(client_id)) if client_id else list_farms()
        return jsonify([dict(r) for r in rows])
@app.route('/movement', methods=['POST'])
def api_movement():
    data = request.json
    required = ['type','client_id','product','weight']
    if not data or any(k not in data for k in required): return jsonify({'error': 'required fields: type, client_id, product, weight'}), 400
    mov_id = register_movement(data['type'], data['client_id'], data.get('farm_id'), data.get('driver'), data.get('vehicle'), data['product'], float(data['weight']), data.get('humidity'), data.get('impurity'))
    pdf = generate_ticket_pdf(mov_id)
    return jsonify({'ok':True, 'id':mov_id, 'ticket': pdf}), 201
@app.route('/stock', methods=['GET'])
def api_stock():
    return jsonify(compute_stock())


# ---------- Main ----------
if __name__ == '__main__':
    parser = argparse.ArgumentParser()
    parser.add_argument('--mode', choices=['gui', 'api'], default='gui')
    parser.add_argument('--host', default='127.0.0.1')
    parser.add_argument('--port', type=int, default=5000)
    args = parser.parse_args()
    init_db()
    if args.mode == 'gui':
        # ----> MUDANÇA 2: Executando a nova classe renomeada
        MainApp().run()
    else:
        print(f'Iniciando API em http://{args.host}:{args.port}')
        app.run(host=args.host, port=args.port)