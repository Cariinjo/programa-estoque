import customtkinter as ctk
from tkinter import ttk, messagebox
import tkinter as tk  # Adicionado para o uso de tk.END
from funcoes_produto import Produto
from POO_banco_e_py import Banco

def clique():
    banco = Banco("banco_py")

    menu_tabela = ctk.CTkToplevel()
    menu_tabela.title("Painel de Estoque")
    menu_tabela.geometry("900x600")
    menu_tabela.grid_columnconfigure(0, weight=1)
    menu_tabela.grid_rowconfigure(0, weight=1)

    # ------------------ TABELA ------------------ #
    frame1 = ctk.CTkFrame(master=menu_tabela, corner_radius=10, fg_color="transparent")
    frame1.grid(row=0, column=0, padx=10, pady=(10, 0), sticky="nsew")
    frame1.grid_columnconfigure(0, weight=1)
    frame1.grid_rowconfigure(1, weight=1)

    label_titulo = ctk.CTkLabel(frame1, text="Painel de Estoque", font=("Arial", 20, "bold"))
    label_titulo.grid(row=0, column=0, pady=10)

    tabela = ttk.Treeview(frame1, columns=("Nome", "Categoria", "Validade", "Preço", "Quantidade"), show="headings")
    for col in ("Nome", "Categoria", "Validade", "Preço", "Quantidade"):
        tabela.heading(col, text=col)
        tabela.column(col, anchor="center")
    tabela.grid(row=1, column=0, padx=10, pady=10, sticky="nsew")
    tabela.bind("<Double-1>", lambda event: selecionar_linha(event))

    def atualizar_tabela():
        tabela.delete(*tabela.get_children())
        banco.cursor.execute("SELECT * FROM produtos")
        produtos = banco.cursor.fetchall()
        for produto in produtos:
            tabela.insert("", "end", values=produto[1:])

    atualizar_tabela()

    # ------------------ BARRA DE PESQUISA ------------------ #
    frame_pesquisa = ctk.CTkFrame(master=menu_tabela, corner_radius=10, fg_color="transparent")
    frame_pesquisa.grid(row=1, column=0, padx=10, pady=10, sticky="ew")
    frame_pesquisa.grid_columnconfigure(1, weight=1)

    label_pesquisa = ctk.CTkLabel(frame_pesquisa, text="Pesquisar Produto:", font=("Arial", 14))
    label_pesquisa.grid(row=0, column=0, padx=5)

    entry_pesquisa = ctk.CTkEntry(frame_pesquisa, placeholder_text="Digite o nome do produto")
    entry_pesquisa.grid(row=0, column=1, padx=5, sticky="ew")

    def pesquisar():
        nome = entry_pesquisa.get()
        if not nome:
            messagebox.showwarning("Aviso", "Informe o nome do produto para pesquisar.")
            return
        banco.cursor.execute("SELECT * FROM produtos WHERE nome LIKE ?", ('%' + nome + '%',))
        resultados = banco.cursor.fetchall()
        tabela.delete(*tabela.get_children())
        for produto in resultados:
            tabela.insert("", "end", values=produto[1:])
        if not resultados:
            messagebox.showinfo("Resultado", "Nenhum produto encontrado com esse nome.")
        entry_pesquisa.delete(0, tk.END)

    botao_pesquisar = ctk.CTkButton(frame_pesquisa, text="Pesquisar", command=pesquisar)
    botao_pesquisar.grid(row=0, column=2, padx=5)

    # ------------------ BOTÕES ------------------ #
    frame_botoes = ctk.CTkFrame(master=menu_tabela, corner_radius=10, fg_color="transparent")
    frame_botoes.grid(row=2, column=0, padx=10, pady=10, sticky="ew")
    frame_botoes.grid_columnconfigure((0, 1, 2, 3), weight=1)

    def selecionar_linha(event):
        item = tabela.selection()[0]
        produto = tabela.item(item, "values")
        messagebox.showinfo("Produto Selecionado", f"Nome: {produto[0]}\nCategoria: {produto[1]}\nValidade: {produto[2]}\nPreço: {produto[3]}\nQuantidade: {produto[4]}")

    def adicionar_produto():
        menu_tabela_adicionar = ctk.CTkToplevel(menu_tabela)
        menu_tabela_adicionar.title("Adicionar Produto")
        menu_tabela_adicionar.geometry("400x300")

        def salvar_produto():
            nome = entry_nome.get()
            categoria = entry_categoria.get()
            validade = entry_validade.get()
            preco = entry_preco.get()
            quantidade = entry_quantidade.get()

            if not (nome and categoria and validade and preco and quantidade):
                messagebox.showwarning("Erro", "Preencha todos os campos!")
                return

            try:
                preco = float(preco)
                quantidade = int(quantidade)
                produto = Produto(nome, categoria, validade, preco, quantidade)
                produto.inserir_no_banco(banco)
                atualizar_tabela()
                menu_tabela_adicionar.destroy()
            except ValueError as e:
                messagebox.showerror("Erro", f"Valor inválido: {e}")

        entry_nome = ctk.CTkEntry(menu_tabela_adicionar, placeholder_text="Nome")
        entry_nome.pack(pady=5)
        entry_categoria = ctk.CTkEntry(menu_tabela_adicionar, placeholder_text="Categoria")
        entry_categoria.pack(pady=5)
        entry_validade = ctk.CTkEntry(menu_tabela_adicionar, placeholder_text="Validade")
        entry_validade.pack(pady=5)
        entry_preco = ctk.CTkEntry(menu_tabela_adicionar, placeholder_text="Preço")
        entry_preco.pack(pady=5)
        entry_quantidade = ctk.CTkEntry(menu_tabela_adicionar, placeholder_text="Quantidade")
        entry_quantidade.pack(pady=5)

        botao_salvar = ctk.CTkButton(menu_tabela_adicionar, text="Salvar", command=salvar_produto)
        botao_salvar.pack(pady=10)

    def deletar_produto():
        item = tabela.selection()
        if not item:
            messagebox.showwarning("Aviso", "Selecione um produto para deletar.")
            return
        produto = tabela.item(item[0], "values")
        nome = produto[0]
        banco.cursor.execute("DELETE FROM produtos WHERE nome = ?", (nome,))
        banco.conn.commit()
        atualizar_tabela()
        messagebox.showinfo("Sucesso", f"Produto '{nome}' deletado com sucesso!")

    def atualizar_produto():
        item = tabela.selection()
        if not item:
            messagebox.showwarning("Aviso", "Selecione um produto para atualizar.")
            return
        produto = tabela.item(item[0], "values")
        nome = produto[0]

        menu_tabela_atualizar = ctk.CTkToplevel(menu_tabela)
        menu_tabela_atualizar.title("Atualizar Produto")
        menu_tabela_atualizar.geometry("400x300")

        def salvar_atualizacao():
            categoria = entry_categoria.get()
            validade = entry_validade.get()
            preco = entry_preco.get()
            quantidade = entry_quantidade.get()

            if not (categoria and validade and preco and quantidade):
                messagebox.showwarning("Erro", "Preencha todos os campos!")
                return

            try:
                preco = float(preco)
                quantidade = int(quantidade)
                banco.cursor.execute(
                    "UPDATE produtos SET categoria = ?, validade = ?, preco = ?, quantidade = ? WHERE nome = ?",
                    (categoria, validade, preco, quantidade, nome)
                )
                banco.conn.commit()
                atualizar_tabela()
                menu_tabela_atualizar.destroy()
            except ValueError as e:
                messagebox.showerror("Erro", f"Valor inválido: {e}")

        entry_categoria = ctk.CTkEntry(menu_tabela_atualizar)
        entry_categoria.insert(0, produto[1])
        entry_categoria.pack(pady=5)
        entry_validade = ctk.CTkEntry(menu_tabela_atualizar)
        entry_validade.insert(0, produto[2])
        entry_validade.pack(pady=5)
        entry_preco = ctk.CTkEntry(menu_tabela_atualizar)
        entry_preco.insert(0, produto[3])
        entry_preco.pack(pady=5)
        entry_quantidade = ctk.CTkEntry(menu_tabela_atualizar)
        entry_quantidade.insert(0, produto[4])
        entry_quantidade.pack(pady=5)

        botao_salvar = ctk.CTkButton(menu_tabela_atualizar, text="Salvar", command=salvar_atualizacao)
        botao_salvar.pack(pady=10)

    botao_adicionar = ctk.CTkButton(frame_botoes, text="Adicionar Produto", command=adicionar_produto)
    botao_adicionar.grid(row=0, column=0, padx=5, pady=5, sticky="ew")

    botao_deletar = ctk.CTkButton(frame_botoes, text="Deletar Produto", command=deletar_produto)
    botao_deletar.grid(row=0, column=1, padx=5, pady=5, sticky="ew")

    botao_atualizar = ctk.CTkButton(frame_botoes, text="Atualizar Produto", command=atualizar_produto)
    botao_atualizar.grid(row=0, column=2, padx=5, pady=5, sticky="ew")

    botao_fechar = ctk.CTkButton(frame_botoes, text="Fechar", command=menu_tabela.destroy)
    botao_fechar.grid(row=0, column=3, padx=5, pady=5, sticky="ew")

    menu_tabela.protocol("WM_DELETE_WINDOW", lambda: (banco.fechar_conexao(), menu_tabela.destroy()))
    menu_tabela.mainloop()
