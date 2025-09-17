# Protótipo: Gestão de Silos (Desktop Tkinter) + API mínima (Flask)
# Arquivo: Gestao_Silos_Prototipo.py
# Como usar:
# 1) Instale dependências: pip install reportlab flask
# 2) Execute em modo GUI (padrão): python Gestao_Silos_Prototipo.py
#    ou execute a API: python Gestao_Silos_Prototipo.py --mode api
# Tickets em PDF são gerados na pasta 'tickets/'

import os
import sqlite3
import argparse
from datetime import datetime
from reportlab.pdfgen import canvas
from reportlab.lib.pagesizes import A6
import tkinter as tk
from tkinter import ttk, messagebox
from flask import Flask, request, jsonify

DB_FILE = 'silos.db'
TICKETS_DIR = 'tickets'
os.makedirs(TICKETS_DIR, exist_ok=True)

# ---------- Banco de dados ----------
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

# ---------- Funções de negócio ----------

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
    # aggregate
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

# ---------- Geração de ticket PDF ----------

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
        if y < 20:
            c.showPage()
            y = height - 20
    c.showPage()
    c.save()
    return filename

# ---------- GUI (Tkinter) ----------
class App(tk.Tk):
    def __init__(self):
        super().__init__()
        self.title('Gestão de Silos - Protótipo')
        self.geometry('820x520')
        self.create_widgets()
        self.refresh_clients()
        self.refresh_movements()
        self.refresh_stock()

    def create_widgets(self):
        nb = ttk.Notebook(self)
        nb.pack(fill='both', expand=True)
        # Clients tab
        tab_clients = ttk.Frame(nb)
        nb.add(tab_clients, text='Clientes / Fazendas')
        self.build_clients_tab(tab_clients)
        # Movements tab
        tab_mov = ttk.Frame(nb)
        nb.add(tab_mov, text='Entrada / Saída')
        self.build_movements_tab(tab_mov)
        # Stock tab
        tab_stock = ttk.Frame(nb)
        nb.add(tab_stock, text='Estoque')
        self.build_stock_tab(tab_stock)
        # Log / Movements list
        tab_log = ttk.Frame(nb)
        nb.add(tab_log, text='Últimos movimentos')
        self.build_log_tab(tab_log)

    def build_clients_tab(self, parent):
        frm = ttk.Frame(parent, padding=8)
        frm.pack(fill='both', expand=True)
        left = ttk.Frame(frm)
        left.pack(side='left', fill='y')
        ttk.Label(left, text='Novo Cliente').pack(anchor='w')
        self.client_name_var = tk.StringVar()
        ttk.Entry(left, textvariable=self.client_name_var).pack(fill='x')
        self.client_doc_var = tk.StringVar()
        ttk.Entry(left, textvariable=self.client_doc_var).pack(fill='x')
        ttk.Button(left, text='Adicionar Cliente', command=self.on_add_client).pack(pady=6)

        ttk.Separator(frm, orient='vertical').pack(side='left', fill='y', padx=8)
        right = ttk.Frame(frm)
        right.pack(fill='both', expand=True)
        ttk.Label(right, text='Fazenda para Cliente Selecionado').pack(anchor='w')
        self.clients_combo = ttk.Combobox(right, state='readonly')
        self.clients_combo.pack(fill='x')
        self.farm_name_var = tk.StringVar()
        ttk.Entry(right, textvariable=self.farm_name_var).pack(fill='x')
        ttk.Button(right, text='Adicionar Fazenda', command=self.on_add_farm).pack(pady=6)
        ttk.Label(right, text='Fazendas existentes:').pack(anchor='w', pady=(8,0))
        self.farms_list = tk.Listbox(right, height=8)
        self.farms_list.pack(fill='both', expand=True)

    def build_movements_tab(self, parent):
        frm = ttk.Frame(parent, padding=8)
        frm.pack(fill='both', expand=True)
        left = ttk.Frame(frm)
        left.pack(side='left', fill='both', expand=True)
        ttk.Label(left, text='Registrar Movimento').pack(anchor='w')
        self.mov_type_var = tk.StringVar(value='entry')
        ttk.Radiobutton(left, text='Entrada', variable=self.mov_type_var, value='entry').pack(anchor='w')
        ttk.Radiobutton(left, text='Saída', variable=self.mov_type_var, value='exit').pack(anchor='w')

        ttk.Label(left, text='Cliente').pack(anchor='w')
        self.mov_client_combo = ttk.Combobox(left, state='readonly')
        self.mov_client_combo.pack(fill='x')
        ttk.Label(left, text='Fazenda (opcional)').pack(anchor='w')
        self.mov_farm_combo = ttk.Combobox(left, state='readonly')
        self.mov_farm_combo.pack(fill='x')

        ttk.Label(left, text='Motorista').pack(anchor='w')
        self.mov_driver_var = tk.StringVar()
        ttk.Entry(left, textvariable=self.mov_driver_var).pack(fill='x')
        ttk.Label(left, text='Veículo').pack(anchor='w')
        self.mov_vehicle_var = tk.StringVar()
        ttk.Entry(left, textvariable=self.mov_vehicle_var).pack(fill='x')
        ttk.Label(left, text='Produto').pack(anchor='w')
        self.mov_product_var = tk.StringVar()
        ttk.Entry(left, textvariable=self.mov_product_var).pack(fill='x')
        ttk.Label(left, text='Peso (kg)').pack(anchor='w')
        self.mov_weight_var = tk.StringVar()
        ttk.Entry(left, textvariable=self.mov_weight_var).pack(fill='x')
        ttk.Label(left, text='Umidade (%)').pack(anchor='w')
        self.mov_humidity_var = tk.StringVar()
        ttk.Entry(left, textvariable=self.mov_humidity_var).pack(fill='x')
        ttk.Label(left, text='Impureza (%)').pack(anchor='w')
        self.mov_impurity_var = tk.StringVar()
        ttk.Entry(left, textvariable=self.mov_impurity_var).pack(fill='x')

        ttk.Button(left, text='Registrar e Gerar Ticket', command=self.on_register_movement).pack(pady=8)

        right = ttk.Frame(frm)
        right.pack(side='left', fill='both', expand=True)
        ttk.Label(right, text='Histórico (últimos mov) - selecione para gerar ticket novamente').pack(anchor='w')
        self.mov_list = tk.Listbox(right)
        self.mov_list.pack(fill='both', expand=True)
        ttk.Button(right, text='Gerar ticket do selecionado', command=self.on_generate_selected_ticket).pack(pady=6)

    def build_stock_tab(self, parent):
        frm = ttk.Frame(parent, padding=8)
        frm.pack(fill='both', expand=True)
        ttk.Label(frm, text='Estoque por produto (kg)').pack(anchor='w')
        self.stock_tree = ttk.Treeview(frm, columns=('product','kg'), show='headings')
        self.stock_tree.heading('product', text='Produto')
        self.stock_tree.heading('kg', text='Kg')
        self.stock_tree.pack(fill='both', expand=True)
        ttk.Button(frm, text='Atualizar', command=self.refresh_stock).pack(pady=8)

    def build_log_tab(self, parent):
        frm = ttk.Frame(parent, padding=8)
        frm.pack(fill='both', expand=True)
        self.log_tree = ttk.Treeview(frm, columns=('id','type','client','farm','product','weight','time'), show='headings')
        for c, t in [('id','ID'),('type','Tipo'),('client','Cliente'),('farm','Fazenda'),('product','Produto'),('weight','Peso(kg)'),('time','Data')]:
            self.log_tree.heading(c, text=t)
        self.log_tree.pack(fill='both', expand=True)
        ttk.Button(frm, text='Atualizar', command=self.refresh_movements).pack(pady=6)

    # ---------- Callbacks ----------
    def on_add_client(self):
        name = self.client_name_var.get().strip()
        doc = self.client_doc_var.get().strip()
        if not name:
            messagebox.showwarning('Aviso','Informe o nome do cliente')
            return
        add_client(name, doc or None)
        messagebox.showinfo('Sucesso','Cliente adicionado')
        self.client_name_var.set('')
        self.client_doc_var.set('')
        self.refresh_clients()

    def on_add_farm(self):
        sel = self.clients_combo.get()
        if not sel:
            messagebox.showwarning('Aviso','Selecione um cliente')
            return
        # combobox value is like '1 - Cliente'
        client_id = int(sel.split(' - ',1)[0])
        name = self.farm_name_var.get().strip()
        if not name:
            messagebox.showwarning('Aviso','Informe o nome da fazenda')
            return
        add_farm(client_id, name)
        messagebox.showinfo('Sucesso','Fazenda adicionada')
        self.farm_name_var.set('')
        self.refresh_clients()

    def on_register_movement(self):
        try:
            mtype = self.mov_type_var.get()
            client_sel = self.mov_client_combo.get()
            if not client_sel:
                messagebox.showwarning('Aviso','Selecione um cliente')
                return
            client_id = int(client_sel.split(' - ',1)[0])
            farm_sel = self.mov_farm_combo.get()
            farm_id = int(farm_sel.split(' - ',1)[0]) if farm_sel else None
            driver = self.mov_driver_var.get().strip()
            vehicle = self.mov_vehicle_var.get().strip()
            product = self.mov_product_var.get().strip() or 'N/A'
            weight = float(self.mov_weight_var.get().strip())
            humidity = float(self.mov_humidity_var.get().strip()) if self.mov_humidity_var.get().strip() else None
            impurity = float(self.mov_impurity_var.get().strip()) if self.mov_impurity_var.get().strip() else None
        except Exception as e:
            messagebox.showerror('Erro','Confira os dados: ' + str(e))
            return
        mov_id = register_movement(mtype, client_id, farm_id, driver or None, vehicle or None, product, weight, humidity, impurity)
        pdf = generate_ticket_pdf(mov_id)
        messagebox.showinfo('Registrado', f'Movimento registrado e ticket gerado:\n{pdf}')
        self.refresh_movements()
        self.refresh_stock()

    def on_generate_selected_ticket(self):
        sel = self.mov_list.curselection()
        if not sel:
            messagebox.showwarning('Aviso','Selecione um movimento na lista')
            return
        data = self.mov_list.get(sel[0])
        mov_id = int(data.split('|')[0].strip())
        pdf = generate_ticket_pdf(mov_id)
        messagebox.showinfo('Ticket', f'Ticket gerado:\n{pdf}')

    def refresh_clients(self):
        clients = list_clients()
        combo_vals = [f"{c['id']} - {c['name']}" for c in clients]
        self.clients_combo['values'] = combo_vals
        self.mov_client_combo['values'] = combo_vals
        self.clients_combo.set('')
        self.mov_client_combo.set('')
        # refresh farms list for selection
        self.farms_list.delete(0, tk.END)
        if clients:
            # show farms for first client by default
            first_id = clients[0]['id']
            farms = list_farms(first_id)
            for f in farms:
                self.farms_list.insert(tk.END, f"{f['id']} - {f['name']}")
        # update farm combobox values (all farms)
        farms_all = list_farms()
        farm_combo_vals = [f"{f['id']} - {f['name']}" for f in farms_all]
        self.mov_farm_combo['values'] = [''] + farm_combo_vals

    def refresh_movements(self):
        rows = list_movements(500)
        self.mov_list.delete(0, tk.END)
        for r in rows:
            text = f"{r['id']} | {r['type']} | {r['client_name']} | {r['farm_name'] or '-'} | {r['product']} | {r['weight']:.3f}kg | {r['timestamp']}"
            self.mov_list.insert(tk.END, text)
        # also update log tree
        for i in self.log_tree.get_children():
            self.log_tree.delete(i)
        for r in rows:
            self.log_tree.insert('', tk.END, values=(r['id'], r['type'], r['client_name'], r['farm_name'] or '-', r['product'], f"{r['weight']:.3f}", r['timestamp']))

    def refresh_stock(self):
        products = compute_stock()
        for i in self.stock_tree.get_children():
            self.stock_tree.delete(i)
        for prod, kg in products.items():
            self.stock_tree.insert('', tk.END, values=(prod, f"{kg:.3f}"))

# ---------- API mínima (Flask) ----------
app = Flask(__name__)

@app.route('/clients', methods=['GET','POST'])
def api_clients():
    if request.method == 'POST':
        data = request.json
        if not data or 'name' not in data:
            return jsonify({'error':'name required'}), 400
        add_client(data['name'], data.get('doc'))
        return jsonify({'ok':True}), 201
    else:
        rows = list_clients()
        return jsonify([dict(r) for r in rows])

@app.route('/farms', methods=['GET','POST'])
def api_farms():
    if request.method == 'POST':
        data = request.json
        if not data or 'client_id' not in data or 'name' not in data:
            return jsonify({'error':'client_id and name required'}), 400
        add_farm(data['client_id'], data['name'])
        return jsonify({'ok':True}), 201
    else:
        client_id = request.args.get('client_id')
        if client_id:
            rows = list_farms(int(client_id))
        else:
            rows = list_farms()
        return jsonify([dict(r) for r in rows])

@app.route('/movement', methods=['POST'])
def api_movement():
    data = request.json
    required = ['type','client_id','product','weight']
    if not data or any(k not in data for k in required):
        return jsonify({'error': 'required fields: type, client_id, product, weight'}), 400
    mov_id = register_movement(data['type'], data['client_id'], data.get('farm_id'), data.get('driver'), data.get('vehicle'), data['product'], float(data['weight']), data.get('humidity'), data.get('impurity'))
    pdf = generate_ticket_pdf(mov_id)
    return jsonify({'ok':True, 'id':mov_id, 'ticket': pdf}), 201

@app.route('/stock', methods=['GET'])
def api_stock():
    return jsonify(compute_stock())

# ---------- Main ----------
if __name__ == '__main__':
    parser = argparse.ArgumentParser()
    parser.add_argument('--mode', choices=['gui','api'], default='gui')
    parser.add_argument('--host', default='127.0.0.1')
    parser.add_argument('--port', type=int, default=5000)
    args = parser.parse_args()
    init_db()
    if args.mode == 'gui':
        app_tk = App()
        app_tk.mainloop()
    else:
        print(f'Iniciando API em http://{args.host}:{args.port} (endpoints: /clients /farms /movement /stock)')
        app.run(host=args.host, port=args.port)
