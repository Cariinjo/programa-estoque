#import tkinter as tk
import customtkinter as ctk

import POO_banco_e_py
from funcao_usuario import listar_usuarios

ctk.set_appearance_mode("dark")
ctk.set_default_color_theme("green")

conectar = POO_banco_e_py.Banco("banco_py")
#criar janela
janela = ctk.CTk()

#definir o tirulo da janela
janela.title("Minha primeira janela")

#definir o tamanho da janela
janela.geometry("700x800")

#criar um label/texto
label = ctk.CTkLabel(janela,text="interface grafica com professor Ricardo!")
label.pack()

label = ctk.CTkLabel(janela,text="Teste outra frase")
label.pack()

label = ctk.CTkLabel(janela,text="Teste de botão")
label.pack()

#criar um botão
def clique():
    nova_janela = ctk.CTkToplevel()
    nova_janela.title("Doação de rin")
    nova_janela.geometry("300x300")

    def vericar():
        if var.get() == 1:
            nova_janela = ctk.CTkToplevel()
            nova_janela.title("Rin doado com sucesso!")
            nova_janela.geometry("300x200")

            label = ctk.CTkLabel(nova_janela,text="Você aceitou doar seu rin!")
            label.pack(pady=20)

            botao = ctk.CTkButton(nova_janela,text="fechar", command=nova_janela.destroy)
            botao.pack(pady=20)

        else:
            nova_janela = ctk.CTkToplevel()
            nova_janela.title("Rin não doado.")
            nova_janela.geometry("300x200")

            label = ctk.CTkLabel(nova_janela,text="Você nao aceitou doar seu rin!")
            label.pack(pady=20)

            botao = ctk.CTkButton(nova_janela,text="fechar", command=nova_janela.destroy)
            botao.pack(pady=20)

    label = ctk.CTkLabel(nova_janela,text="Aceita doar seu rin?")
    label.pack(pady=20)

    var = ctk.IntVar()
    check = ctk.CTkCheckBox(nova_janela, text="aceito doar o rin.", variable=var)
    check.pack(pady=20)

    botao=ctk.CTkButton(nova_janela,text="verificar", command=vericar)
    botao.pack(pady=20)

    botao = ctk.CTkButton(nova_janela,text="fechar", command=nova_janela.destroy)
    botao.pack()

botao = ctk.CTkButton(janela,text="Doar rin!", command=clique)
botao.pack(pady=20)

entrada=ctk.CTkEntry(janela)
entrada.pack(pady=20)

texto = ctk.CTkTextbox(janela,height=5,width=30)
texto.pack(pady=20)

def vericar():
    if var.get() == 1:
        nova_janela = ctk.CTkToplevel()
        nova_janela.title("Verificação de termos.")
        nova_janela.geometry("300x200")

        label = ctk.CTkLabel(nova_janela,text="Parabens você é um humano!")
        label.pack(pady=20)

        botao = ctk.CTkButton(nova_janela,text="fechar", command=nova_janela.destroy)
        botao.pack(pady=20)

    else:
        nova_janela = ctk.CTkToplevel()
        nova_janela.title("Verificação de termos.")
        nova_janela.geometry("300x200")

        label = ctk.CTkLabel(nova_janela,text="Que triste, Você é um robo!")
        label.pack(pady=20)

        botao = ctk.CTkButton(nova_janela,text="fechar", command=nova_janela.destroy)
        botao.pack(pady=20)

var = ctk.IntVar()
check = ctk.CTkCheckBox(janela, text="Não sou um robo", variable=var)
check.pack(pady=20)

botao=ctk.CTkButton(janela,text="verificar", command=vericar)
botao.pack(pady=20)

# Label para exibir os usuários - crie isso ANTES do botão
label_usuarios = ctk.CTkLabel(janela, text="", justify="left", anchor="w", wraplength=380)
label_usuarios.pack(padx=10, pady=10)

# Botão que chama a função listar_usuarios com argumentos
botao = ctk.CTkButton(
    janela,
    text="Listar Usuários",
    command=lambda: listar_usuarios(conectar.cursor, label_usuarios)
)
botao.pack(pady=10)

opcao = ctk.StringVar()
opcao.set(None)

radio1 = ctk.CTkRadioButton(janela,text="opção 1",variable=opcao,value="1")
radio2 = ctk.CTkRadioButton(janela,text="opção 2",variable=opcao,value="2")

radio1.pack(pady=20)
radio2.pack(pady=20)

#fechar programa
botao = ctk.CTkButton(janela,text="fechar", command=janela.destroy)
botao.pack(pady=20)

#iniciar o loop da interface
janela.mainloop()

