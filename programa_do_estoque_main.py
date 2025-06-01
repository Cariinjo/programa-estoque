import customtkinter as ctk
from tkinter import messagebox
from PIL import Image
from POO_banco_e_py import Banco
from inserir_usuario import Pessoa
from estoque_painel import clique

# Tema
ctk.set_appearance_mode("dark")
ctk.set_default_color_theme("dark-blue")

# Autenticação
def vai_la(nome_usuario, senha_usuario, banco_nome="banco_py"):
    try:
        conectar = Banco(banco_nome)
    except Exception as e:
        print(f"Erro na conexão: {e}")
        return False

    try:
        conectar.cursor.execute("SELECT senha FROM usuarios WHERE nome = ?", (nome_usuario,))
        resultado = conectar.cursor.fetchone()

        if resultado is None:
            print("Usuário não encontrado.")
            conectar.fechar_conexao()
            return False

        senha_no_banco = resultado[0]

        if senha_usuario == senha_no_banco:
            print("Usuário autenticado com sucesso!")
            conectar.fechar_conexao()
            return True
        else:
            print("Senha incorreta.")
            conectar.fechar_conexao()
            return False

    except Exception as e:
        print(f"Erro ao consultar o banco: {e}")
        conectar.fechar_conexao()
        return False

# Nova janela após login


# Alternar visibilidade da senha
imagem = Image.open("olho_aberto.png")
imagem_olho_a = ctk.CTkImage(light_image=imagem, dark_image=imagem, size=(25, 25))
imagem = Image.open("olho_fechado.png")
imagem_olho_f = ctk.CTkImage(light_image=imagem, dark_image=imagem, size=(25, 25))

def alternar_senha():
    global mostrar
    if mostrar:
        entry2.configure(show="*")
        botao_mostrar.configure(image=imagem_olho_f)
    else:
        entry2.configure(show="")
        botao_mostrar.configure(image=imagem_olho_a)
    mostrar = not mostrar

mostrar = False

# Verificação de login
def verificar_login():
    nome = entry1.get()
    senha = entry2.get()

    if not nome or not senha:
        messagebox.showwarning("Aviso", "Por favor, preencha todos os campos.")
        return

    if vai_la(nome, senha):
        messagebox.showinfo("Login", "Login realizado com sucesso!")
        janela.destroy()
        clique()
        
    else:
        messagebox.showerror("Erro", "Usuário ou senha incorretos.")

# Nova janela de cadastro
def abrir_cadastro():
    cadastro_janela = ctk.CTkToplevel()
    cadastro_janela.title("Cadastro de Usuário")
    cadastro_janela.geometry("400x500")
    cadastro_janela.grab_set()

    def salvar_usuario():
        nome = entry_nome.get()
        idade = entry_idade.get()
        sobrenome = entry_sobrenome.get()
        cidade = entry_cidade.get()
        senha = entry_senha.get()

        if not (nome and idade and sobrenome and cidade and senha):
            messagebox.showwarning("Erro", "Preencha todos os campos!")
            return

        try:
            idade = int(idade)
        except ValueError:
            messagebox.showerror("Erro", "Idade deve ser um número.")
            return

        try:
            user = Pessoa(nome, idade, sobrenome, cidade, senha)
            banco = Banco("banco_py")
            user.inserir_no_banco(banco)
            banco.fechar_conexao()
            messagebox.showinfo("Sucesso", "Usuário cadastrado com sucesso!")
            cadastro_janela.destroy()
        except Exception as e:
            messagebox.showerror("Erro", f"Erro ao salvar usuário: {e}")

    label_titulo = ctk.CTkLabel(cadastro_janela, text="Cadastro", font=("Arial", 20, "bold"))
    label_titulo.pack(pady=10)

    entry_nome = ctk.CTkEntry(cadastro_janela, placeholder_text="Nome")
    entry_nome.pack(pady=10)

    entry_sobrenome = ctk.CTkEntry(cadastro_janela, placeholder_text="Sobrenome")
    entry_sobrenome.pack(pady=10)

    entry_idade = ctk.CTkEntry(cadastro_janela, placeholder_text="Idade")
    entry_idade.pack(pady=10)

    entry_cidade = ctk.CTkEntry(cadastro_janela, placeholder_text="Cidade")
    entry_cidade.pack(pady=10)

    entry_senha = ctk.CTkEntry(cadastro_janela, placeholder_text="Senha", show="*")
    entry_senha.pack(pady=10)

    botao_salvar = ctk.CTkButton(cadastro_janela, text="Salvar", command=salvar_usuario)
    botao_salvar.pack(pady=20)

# Interface principal
janela = ctk.CTk()
janela.title("Login - Programa de Teste")
janela.resizable(False, False)
janela.geometry("700x500")

# Frame 1 (logo)
frame1 = ctk.CTkFrame(master=janela, corner_radius=10, fg_color="transparent")
frame1.grid(row=0, column=0, padx=10, pady=10, sticky="nsew")
imagem = Image.open("logo.png")
imagem_ctk = ctk.CTkImage(light_image=imagem, dark_image=imagem, size=(100, 100))
label_imagem = ctk.CTkLabel(master=frame1, image=imagem_ctk, text="")
label_imagem.grid(row=0, column=0, padx=10, pady=10, sticky="ew")

# Frame 2 (título)
frame2 = ctk.CTkFrame(master=janela, corner_radius=10, fg_color="transparent")
frame2.grid(row=0, column=1, columnspan=7, padx=10, pady=10, sticky="ew")
label = ctk.CTkLabel(master=frame2, text="Bem vindo ao programa de teste:", text_color="white", font=("Arial", 30, "bold"), justify="center")
label.grid(row=0, column=0, columnspan=7, padx=10, pady=10, sticky="ew")

# Frame 3 (entradas)
frame3 = ctk.CTkFrame(master=janela, corner_radius=10, fg_color="transparent")
frame3.grid(row=2, column=0, columnspan=7, padx=10, pady=10, sticky="")
label1 = ctk.CTkLabel(master=frame3, text="Usuário:")
label1.grid(row=0, column=0, pady=(10, 1), padx=(10), sticky="w")

entry1 = ctk.CTkEntry(master=frame3, placeholder_text="Digite seu usuário:")
entry1.grid(row=0, column=1, pady=(10, 1), padx=(10), sticky="nsew")

label2 = ctk.CTkLabel(master=frame3, text="Senha:")
label2.grid(row=1, column=0, pady=(10, 1), padx=(10), sticky="w")

entry2 = ctk.CTkEntry(master=frame3, placeholder_text="Digite sua senha:", show="*")
entry2.grid(row=1, column=1, pady=(10, 1), padx=(10), sticky="nsew")

botao_mostrar = ctk.CTkButton(master=frame3, command=alternar_senha, text="", image=imagem_olho_f, width=25, height=25, fg_color="grey", hover_color="grey", corner_radius=10)
botao_mostrar.grid(row=1, column=2, pady=(10, 1), padx=(10), sticky="nsew")

# Frame 4 (botões)
frame4 = ctk.CTkFrame(master=janela, corner_radius=10, fg_color="transparent")
frame4.grid(row=3, column=0, columnspan=7, padx=20, pady=20, sticky="")

botao_entrar = ctk.CTkButton(master=frame4, text="Entrar", command=verificar_login)
botao_entrar.grid(row=0, column=0, pady=(10, 1), padx=(10), sticky="nsew")

botao_cadastrar = ctk.CTkButton(master=frame4, text="Cadastrar", command=abrir_cadastro)
botao_cadastrar.grid(row=1, column=0, pady=(10, 1), padx=(10), sticky="nsew")

botao_fechar = ctk.CTkButton(master=frame4, text="Fechar", command=janela.destroy)
botao_fechar.grid(row=2, column=0, columnspan=7, pady=(10, 1), padx=(10), sticky="nsew")

# Iniciar janela
janela.mainloop()
