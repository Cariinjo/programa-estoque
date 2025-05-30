import customtkinter as ctk
import _tkinter as tk
from tkinter import messagebox
from PIL import Image


ctk.set_appearance_mode("dark")
ctk.set_default_color_theme("dark-blue")
def fechar():
    janela.destroy()
    clique()


#verificar senha
def vai_la():
    usuario = entry1.get()
    senha= entry2.get()

    if usuario == "rafael" and senha == "1234":
        label3.configure(text= "LOGIN FEITO COM SUCESSO", text_color="green")
    else:
        label3.configure (text= "USUARIO INCORETO", text_color="red")

#botao nova janela
def clique():
    nova_janela = ctk.CTk()
    nova_janela.title("Home")
    nova_janela.geometry("400x400")
    nova_janela.lift()
    nova_janela.focus_force()
    nova_janela.grab_set()

    label = ctk.CTkLabel(nova_janela,text="Parabens você entro")
    label.pack(pady=20)

    opcao = ctk.StringVar()
    opcao.set(None)

    radio1 = ctk.CTkRadioButton(nova_janela,text="opção 1",variable=opcao,value="1")
    radio2 = ctk.CTkRadioButton(nova_janela,text="opção 2",variable=opcao,value="2")

    radio1.pack(pady=20)
    radio2.pack(pady=20)

    botao = ctk.CTkButton(nova_janela,text="Fechar", command=nova_janela.destroy)
    botao.pack()


    nova_janela.mainloop()

# Função para mostrar/ocultar a senha
imagem = Image.open("olho_aberto.png")
imagem_olho_a = ctk.CTkImage(light_image=imagem,dark_image=imagem, size=(50,50))

imagem = Image.open("olho_fechado.png")
imagem_olho_f = ctk.CTkImage(light_image=imagem,dark_image=imagem, size=(50,50))

def alternar_senha():
    global mostrar
    if mostrar:
        entry2.configure(show="*")
        #botao_mostrar.configure(text="Mostrar Senha")
        botao_mostrar.configure(image=imagem_olho_f)
    else:
        entry2.configure(show="")
        botao_mostrar.configure(image=imagem_olho_a)
        #botao_mostrar.configure(text="Ocultar Senha")
    mostrar = not mostrar
mostrar = False

#mensagem de alerta
def alerta():
    messagebox.showinfo("Aviso","Você clicou no botão!")

#verificar
def vericar():
    if var.get() == 1:
        nova_janela = ctk.CTk(janela)
        nova_janela.title("Verificação de termos.")
        nova_janela.geometry("300x200")
        nova_janela.lift()
        nova_janela.focus_force()
        nova_janela.grab_set()

        label = ctk.CTkLabel(nova_janela,text="Parabéns você é um humano!")
        label.pack(pady=20)

        botao = ctk.CTkButton(nova_janela,text="fechar", command=nova_janela.destroy)
        botao.pack(pady=20)

    else:
        nova_janela = ctk.CTkToplevel()
        nova_janela.title("Verificação de termos.")
        nova_janela.geometry("300x200")
        nova_janela.lift()
        nova_janela.focus_force()
        nova_janela.grab_set()

        label = ctk.CTkLabel(nova_janela,text="Que triste, Você é um robo!")
        label.pack(pady=20)

        botao = ctk.CTkButton(nova_janela,text="fechar", command=nova_janela.destroy)
        botao.pack(pady=20)


#inicio#

janela = ctk.CTk()
janela.title("login/Aula do dia 27/05/2025")
#janela.resizable(False,False)
janela.geometry("1000x800")
#janela.minsize(400,400)
#janela.maxsize(800,800)

#frame1
frame1=ctk.CTkFrame(master=janela, corner_radius=10,fg_color="transparent")
frame1.grid(row=0, column=0,columnspan=7, padx=10, pady=10,sticky="nsew")

imagem = Image.open("logo.png")
imagem_ctk = ctk.CTkImage(light_image=imagem,dark_image=imagem, size=(100,100))

label_imagem=ctk.CTkLabel(frame1, image=imagem_ctk, text="")
label_imagem.grid(row =0, column=0, pady=10,sticky="")

label = ctk.CTkLabel(frame1, text="Bem vindo ao programa de teste:", text_color="white", font=("Arial", 30, "bold"))
label.grid(row=0, column=0, columnspan=7, pady=20, padx=20, sticky="")


#frame2
frame2=ctk.CTkFrame(master=janela, corner_radius=10,fg_color="transparent")
frame2.grid(row=0, column=1, padx=10, pady=10,sticky="nsew")

label1 = ctk.CTkLabel(janela,text="Usuario:")
label1.grid(row = 2, column = 1, pady=(10,1), padx=(10),stick="w")

entry1 = ctk.CTkEntry(janela,placeholder_text="digite seu Usuario:")
entry1.grid(row = 2, column = 2, pady=(10,1), padx=(10),stick="nsew")

label2 = ctk.CTkLabel(janela,text="Senha:")
label2.grid(row = 2, column = 3, pady=(10,1), padx=(10),stick="w")

entry2 = ctk.CTkEntry(janela,placeholder_text="digite sua senha:", show = "*")
entry2.grid(row = 2, column = 4, pady=(10,1), padx=(10),stick="nsew")

botao_mostrar = ctk.CTkButton(janela, command=alternar_senha,text="", image=imagem_olho_f,width=50,height=50,fg_color="grey",hover_color="grey",corner_radius=10)
botao_mostrar.grid(row = 2, column = 5, pady=(10,1), padx=(10),stick="nsew")


botao = ctk.CTkButton(janela,text="Entre", command=vai_la)
botao.grid(row = 2, column = 6, pady=(10,1), padx=(10),stick="nsew")

label3 = ctk.CTkLabel(janela, text="")
label3.grid(row = 3, column = 0, pady=(10,1), padx=(10),stick="nsew")

#frame3
frame3=ctk.CTkFrame(master=janela, corner_radius=10,fg_color="transparent")
frame3.grid(row=1, column=6, padx=10, pady=10,sticky="nsew")



botao = ctk.CTkButton(janela, text="Mostrar Alerta", command=alerta)
botao.grid(row = 4, column = 0, pady=(10), padx=(10),stick="")


botao = ctk.CTkButton(janela,text="nova janela", font=("Arial",20,"bold"), command=clique)
botao.grid(row = 5, column = 0, pady=(10,1), padx=(10),stick="nsew")


var = ctk.IntVar()
check = ctk.CTkCheckBox(janela, text="Não sou um robo", variable=var)
check.grid(row = 6, column = 0, pady=(10,1), padx=(10),stick="nsew")


botao=ctk.CTkButton(janela,text="verificar", command=vericar)
botao.grid(row = 7, column = 0, pady=(10,1), padx=(10),stick="nsew")


botao = ctk.CTkButton(janela,text="Fechar", command=janela.destroy)
botao.grid(row = 8, column = 0, pady=(10,1), padx=(10),stick="nsew")








frame4=ctk.CTkFrame(master=janela, corner_radius=10,fg_color="transparent")
frame4.grid(row=0, column=2, padx=10, pady=10,sticky="nsew")

frame5=ctk.CTkFrame(master=janela, corner_radius=10,fg_color="transparent")
frame5.grid(row=0, column=2, padx=10, pady=10,sticky="nsew")



janela.mainloop()