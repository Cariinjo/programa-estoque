import customtkinter as ctk

import POO_banco_e_py
from funcao_usuario import listar_usuarios

ctk.set_appearance_mode("dark")
#ctk.set_default_color_theme("dark-blue")

conectar = POO_banco_e_py.Banco("banco_py")

janela = ctk.CTk()
janela.title("Minha primeira janela")
janela.geometry("700x800")

label = ctk.CTkLabel(janela, text="interface grafica com professor Ricardo!")
label.pack()

label = ctk.CTkLabel(janela, text="Teste outra frase")
label.pack()

label = ctk.CTkLabel(janela, text="Teste de botão")
label.pack()

def clique():
    nova_janela = ctk.CTkToplevel()
    nova_janela.title("Doação de rin")
    nova_janela.geometry("300x300")

    def vericar():
        if var.get() == 1:
            resultado = ctk.CTkToplevel()
            resultado.title("Rin doado com sucesso!")
            resultado.geometry("300x200")

            label = ctk.CTkLabel(resultado, text="Você aceitou doar seu rin!")
            label.pack(pady=20)

            botao = ctk.CTkButton(resultado, text="fechar", command=resultado.destroy)
            botao.pack(pady=20)

        else:
            resultado = ctk.CTkToplevel()
            resultado.title("Rin não doado.")
            resultado.geometry("300x200")

            label = ctk.CTkLabel(resultado, text="Você nao aceitou doar seu rin!")
            label.pack(pady=20)

            botao = ctk.CTkButton(resultado, text="fechar", command=resultado.destroy)
            botao.pack(pady=20)

    label = ctk.CTkLabel(nova_janela, text="Aceita doar seu rin?")
    label.pack(pady=20)

    var = ctk.IntVar()
    check = ctk.CTkCheckBox(nova_janela, text="aceito doar o rin.", variable=var)
    check.pack(pady=20)

    botao = ctk.CTkButton(nova_janela, text="verificar", command=vericar)
    botao.pack(pady=20)

    botao = ctk.CTkButton(nova_janela, text="fechar", command=nova_janela.destroy)
    botao.pack()

botao = ctk.CTkButton(janela, text="Doar rin!", command=clique)
botao.pack(pady=20)

entrada = ctk.CTkEntry(janela)
entrada.pack(pady=20)

texto = ctk.CTkTextbox(janela, height=100, width=300)
texto.pack(pady=20)

def vericar():
    if var.get() == 1:
        nova_janela = ctk.CTkToplevel()
        nova_janela.title("Verificação de termos.")
        nova_janela.geometry("300x200")

        label = ctk.CTkLabel(nova_janela, text="Parabens você é um humano!")
        label.pack(pady=20)

        botao = ctk.CTkButton(nova_janela, text="fechar", command=nova_janela.destroy)
        botao.pack(pady=20)

    else:
        nova_janela = ctk.CTkToplevel()
        nova_janela.title("Verificação de termos.")
        nova_janela.geometry("300x200")

        label = ctk.CTkLabel(nova_janela, text="Que triste, Você é um robo!")
        label.pack(pady=20)

        botao = ctk.CTkButton(nova_janela, text="fechar", command=nova_janela.destroy)
        botao.pack(pady=20)

var = ctk.IntVar()
check = ctk.CTkCheckBox(janela, text="Não sou um robo", variable=var)
check.pack(pady=20)

botao = ctk.CTkButton(janela, text="verificar", command=vericar)
botao.pack(pady=20)

label_usuarios = ctk.CTkLabel(janela, text="", justify="left", anchor="w", wraplength=380)
label_usuarios.pack(padx=10, pady=10)

botao = ctk.CTkButton(
    janela,
    text="Listar Usuários",
    command=lambda: listar_usuarios(conectar.cursor, label_usuarios)
)
botao.pack(pady=10)

opcao = ctk.StringVar()
opcao.set(None)

radio1 = ctk.CTkRadioButton(janela, text="opção 1", variable=opcao, value="1")
radio2 = ctk.CTkRadioButton(janela, text="opção 2", variable=opcao, value="2")

radio1.pack(pady=20)
radio2.pack(pady=20)

botao = ctk.CTkButton(janela, text="fechar", command=janela.destroy)
botao.pack(pady=20)

janela.mainloop()
