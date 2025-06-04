# estoque_painel.py

import customtkinter as ctk
from tkinter import ttk, messagebox
import tkinter as tk
from funcoes_produto import Produto # Supondo que este arquivo existe e está correto
from POO_banco_e_py import Banco    # Supondo que este arquivo existe e está correto

# Adicione um parâmetro para receber a janela de login
def clique(login_window):
    banco = Banco("banco_py")

    menu_tabela = ctk.CTkToplevel() # Não precisa passar login_window aqui
    menu_tabela.title("Painel de Estoque")
    menu_tabela.geometry("1200x700")
    menu_tabela.grab_set() # Para tornar esta janela modal em relação à login_window (opcional mas bom)
    menu_tabela.grid_columnconfigure(0, weight=1)
    menu_tabela.grid_rowconfigure(0, weight=1) # row 0 deve ser onde o frame1 está

    # ------------------ TABELA ------------------ #
    frame1 = ctk.CTkFrame(master=menu_tabela, corner_radius=10, fg_color="transparent")
    frame1.grid(row=0, column=0, padx=10, pady=(10, 0), sticky="nsew")
    frame1.grid_columnconfigure(0, weight=1)
    frame1.grid_rowconfigure(1, weight=1) # A tabela está na linha 1 deste frame

    label_titulo = ctk.CTkLabel(frame1, text="Painel de Estoque", font=("Arial", 20, "bold"))
    label_titulo.grid(row=0, column=0, pady=10)

    # Colunas da tabela (corrigido para corresponder ao seu uso)
    cols = ("Id", "Nome", "Categoria", "Validade", "Preço", "Quantidade")
    tabela = ttk.Treeview(frame1, columns=cols, show="headings")
    for col in cols:
        tabela.heading(col, text=col)
        tabela.column(col, anchor="center", width=100) # Adicione uma largura base
    tabela.grid(row=1, column=0, padx=10, pady=10, sticky="nsew")
    
    # Função para selecionar linha (ajustada para os índices corretos se ID é o primeiro)
    def selecionar_linha_info(event): # Renomeada para não conflitar se você tiver outra 'selecionar_linha'
        if not tabela.selection():
            return
        item = tabela.selection()[0]
        produto = tabela.item(item, "values")
        # Se 'produto' contém (ID, Nome, Categoria, Validade, Preço, Quantidade)
        messagebox.showinfo("Produto Selecionado", f"ID: {produto[0]}\nNome: {produto[1]}\nCategoria: {produto[2]}\nValidade: {produto[3]}\nPreço: {produto[4]}\nQuantidade: {produto[5]}")

    tabela.bind("<Double-1>", selecionar_linha_info)


    def atualizar_tabela():
        tabela.delete(*tabela.get_children())
        try:
            banco.cursor.execute("SELECT id, nome, categoria, validade, preco, quantidade FROM produtos") # Seja explícito
            produtos = banco.cursor.fetchall()
            for produto_data in produtos:
                tabela.insert("", "end", values=produto_data)
        except Exception as e:
            messagebox.showerror("Erro Banco", f"Erro ao atualizar tabela: {e}")
            print(f"Erro ao atualizar tabela: {e}")


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
            # Simplesmente atualizar para mostrar todos se a pesquisa for vazia
            atualizar_tabela()
            return
        try:
            # Usar LIKE para pesquisa parcial
            banco.cursor.execute("SELECT id, nome, categoria, validade, preco, quantidade FROM produtos WHERE nome LIKE ?", ('%' + nome + '%',))
            resultados = banco.cursor.fetchall()
            tabela.delete(*tabela.get_children())
            for produto_data in resultados:
                tabela.insert("", "end", values=produto_data)
            if not resultados:
                messagebox.showinfo("Resultado", "Nenhum produto encontrado com esse nome.")
            # Não limpar o campo de pesquisa para o usuário ver o que pesquisou
            # entry_pesquisa.delete(0, tk.END)
        except Exception as e:
            messagebox.showerror("Erro Banco", f"Erro ao pesquisar produto: {e}")
            print(f"Erro ao pesquisar produto: {e}")


    botao_pesquisar = ctk.CTkButton(frame_pesquisa, text="Pesquisar", command=pesquisar)
    botao_pesquisar.grid(row=0, column=2, padx=5)
    
    # Botão para limpar pesquisa e recarregar tabela
    def limpar_pesquisa_e_recarregar():
        entry_pesquisa.delete(0, tk.END)
        atualizar_tabela()

    botao_limpar_pesquisa = ctk.CTkButton(frame_pesquisa, text="Limpar", command=limpar_pesquisa_e_recarregar)
    botao_limpar_pesquisa.grid(row=0, column=3, padx=5)


    # ------------------ BOTÕES ------------------ #
    frame_botoes = ctk.CTkFrame(master=menu_tabela, corner_radius=10, fg_color="transparent")
    frame_botoes.grid(row=2, column=0, padx=10, pady=10, sticky="ew")
    frame_botoes.grid_columnconfigure((0, 1, 2, 3), weight=1) # 4 colunas para botões

    def adicionar_produto():
        menu_tabela_adicionar = ctk.CTkToplevel(menu_tabela)
        menu_tabela_adicionar.title("Adicionar Produto")
        menu_tabela_adicionar.geometry("400x350") # Aumentar altura para os labels
        menu_tabela_adicionar.grab_set()
        menu_tabela_adicionar.transient(menu_tabela)


        ctk.CTkLabel(menu_tabela_adicionar, text="Nome:").pack(pady=(10,0))
        entry_nome = ctk.CTkEntry(menu_tabela_adicionar, placeholder_text="Nome do Produto")
        entry_nome.pack(pady=(0,5), fill="x", padx=20)

        ctk.CTkLabel(menu_tabela_adicionar, text="Categoria:").pack(pady=(5,0))
        entry_categoria = ctk.CTkEntry(menu_tabela_adicionar, placeholder_text="Categoria")
        entry_categoria.pack(pady=(0,5), fill="x", padx=20)

        ctk.CTkLabel(menu_tabela_adicionar, text="Validade (AAAA-MM-DD):").pack(pady=(5,0))
        entry_validade = ctk.CTkEntry(menu_tabela_adicionar, placeholder_text="AAAA-MM-DD")
        entry_validade.pack(pady=(0,5), fill="x", padx=20)

        ctk.CTkLabel(menu_tabela_adicionar, text="Preço:").pack(pady=(5,0))
        entry_preco = ctk.CTkEntry(menu_tabela_adicionar, placeholder_text="Ex: 10.99")
        entry_preco.pack(pady=(0,5), fill="x", padx=20)

        ctk.CTkLabel(menu_tabela_adicionar, text="Quantidade:").pack(pady=(5,0))
        entry_quantidade = ctk.CTkEntry(menu_tabela_adicionar, placeholder_text="Ex: 100")
        entry_quantidade.pack(pady=(0,5), fill="x", padx=20)

        def salvar_produto():
            nome = entry_nome.get()
            categoria = entry_categoria.get()
            validade = entry_validade.get()
            preco_str = entry_preco.get()
            quantidade_str = entry_quantidade.get()

            if not (nome and categoria and validade and preco_str and quantidade_str):
                messagebox.showwarning("Erro", "Preencha todos os campos!", parent=menu_tabela_adicionar)
                return

            try:
                preco = float(preco_str)
                quantidade = int(quantidade_str)
                if preco < 0 or quantidade < 0:
                    messagebox.showwarning("Erro", "Preço e quantidade não podem ser negativos.", parent=menu_tabela_adicionar)
                    return

                # Usar a classe Produto de funcoes_produto.py
                produto_obj = Produto(nome, categoria, validade, preco, quantidade)
                produto_obj.inserir_no_banco(banco) # A classe Produto lida com a inserção
                
                atualizar_tabela()
                menu_tabela_adicionar.destroy()
                messagebox.showinfo("Sucesso", "Produto adicionado com sucesso!", parent=menu_tabela)
            except ValueError:
                messagebox.showerror("Erro", "Preço deve ser um número (ex: 10.50) e Quantidade deve ser um inteiro.", parent=menu_tabela_adicionar)
            except Exception as e:
                messagebox.showerror("Erro", f"Erro ao salvar produto: {e}", parent=menu_tabela_adicionar)
                print(f"Erro ao salvar produto: {e}")


        botao_salvar = ctk.CTkButton(menu_tabela_adicionar, text="Salvar", command=salvar_produto)
        botao_salvar.pack(pady=20)

    def deletar_produto():
        selection = tabela.selection()
        if not selection:
            messagebox.showwarning("Aviso", "Selecione um produto para deletar.", parent=menu_tabela)
            return
        
        item = selection[0]
        produto_selecionado = tabela.item(item, "values")
        produto_id = produto_selecionado[0] # ID é o primeiro valor
        produto_nome = produto_selecionado[1] # Nome é o segundo valor

        confirm = messagebox.askyesno("Confirmar", f"Tem certeza que deseja deletar o produto '{produto_nome}' (ID: {produto_id})?", parent=menu_tabela)
        if confirm:
            try:
                banco.cursor.execute("DELETE FROM produtos WHERE id = ?", (produto_id,))
                banco.conn.commit()
                atualizar_tabela()
                messagebox.showinfo("Sucesso", f"Produto '{produto_nome}' deletado com sucesso!", parent=menu_tabela)
            except Exception as e:
                messagebox.showerror("Erro", f"Erro ao deletar produto: {e}", parent=menu_tabela)
                print(f"Erro ao deletar produto: {e}")


    def atualizar_produto_selecionado(): # Renomeado para clareza
        selection = tabela.selection()
        if not selection:
            messagebox.showwarning("Aviso", "Selecione um produto para atualizar.", parent=menu_tabela)
            return
        
        item = selection[0]
        produto_atual = tabela.item(item, "values") # (Id, Nome, Categoria, Validade, Preço, Quantidade)
        produto_id = produto_atual[0]
        
        menu_tabela_atualizar = ctk.CTkToplevel(menu_tabela)
        menu_tabela_atualizar.title("Atualizar Produto")
        menu_tabela_atualizar.geometry("400x380") # Aumentar altura
        menu_tabela_atualizar.grab_set()
        menu_tabela_atualizar.transient(menu_tabela)

        ctk.CTkLabel(menu_tabela_atualizar, text=f"Atualizando Produto ID: {produto_id} - Nome: {produto_atual[1]}").pack(pady=(10,0))
        
        ctk.CTkLabel(menu_tabela_atualizar, text="Novo Nome:").pack(pady=(5,0))
        entry_nome_upd = ctk.CTkEntry(menu_tabela_atualizar)
        entry_nome_upd.insert(0, produto_atual[1]) # Nome
        entry_nome_upd.pack(pady=(0,5), fill="x", padx=20)

        ctk.CTkLabel(menu_tabela_atualizar, text="Nova Categoria:").pack(pady=(5,0))
        entry_categoria_upd = ctk.CTkEntry(menu_tabela_atualizar)
        entry_categoria_upd.insert(0, produto_atual[2]) # Categoria
        entry_categoria_upd.pack(pady=(0,5), fill="x", padx=20)

        ctk.CTkLabel(menu_tabela_atualizar, text="Nova Validade (AAAA-MM-DD):").pack(pady=(5,0))
        entry_validade_upd = ctk.CTkEntry(menu_tabela_atualizar)
        entry_validade_upd.insert(0, produto_atual[3]) # Validade
        entry_validade_upd.pack(pady=(0,5), fill="x", padx=20)

        ctk.CTkLabel(menu_tabela_atualizar, text="Novo Preço:").pack(pady=(5,0))
        entry_preco_upd = ctk.CTkEntry(menu_tabela_atualizar)
        entry_preco_upd.insert(0, str(produto_atual[4])) # Preço
        entry_preco_upd.pack(pady=(0,5), fill="x", padx=20)

        ctk.CTkLabel(menu_tabela_atualizar, text="Nova Quantidade:").pack(pady=(5,0))
        entry_quantidade_upd = ctk.CTkEntry(menu_tabela_atualizar)
        entry_quantidade_upd.insert(0, str(produto_atual[5])) # Quantidade
        entry_quantidade_upd.pack(pady=(0,5), fill="x", padx=20)

        def salvar_atualizacao():
            novo_nome = entry_nome_upd.get()
            nova_categoria = entry_categoria_upd.get()
            nova_validade = entry_validade_upd.get()
            novo_preco_str = entry_preco_upd.get()
            nova_quantidade_str = entry_quantidade_upd.get()

            if not (novo_nome and nova_categoria and nova_validade and novo_preco_str and nova_quantidade_str):
                messagebox.showwarning("Erro", "Preencha todos os campos!", parent=menu_tabela_atualizar)
                return

            try:
                novo_preco = float(novo_preco_str)
                nova_quantidade = int(nova_quantidade_str)
                if novo_preco < 0 or nova_quantidade < 0:
                    messagebox.showwarning("Erro", "Preço e quantidade não podem ser negativos.", parent=menu_tabela_atualizar)
                    return
                
                banco.cursor.execute(
                    "UPDATE produtos SET nome = ?, categoria = ?, validade = ?, preco = ?, quantidade = ? WHERE id = ?",
                    (novo_nome, nova_categoria, nova_validade, novo_preco, nova_quantidade, produto_id)
                )
                banco.conn.commit()
                atualizar_tabela()
                menu_tabela_atualizar.destroy()
                messagebox.showinfo("Sucesso", "Produto atualizado com sucesso!", parent=menu_tabela)
            except ValueError:
                messagebox.showerror("Erro", "Preço deve ser um número e Quantidade um inteiro.", parent=menu_tabela_atualizar)
            except Exception as e:
                messagebox.showerror("Erro", f"Erro ao atualizar produto: {e}", parent=menu_tabela_atualizar)
                print(f"Erro ao atualizar produto: {e}")


        botao_salvar_upd = ctk.CTkButton(menu_tabela_atualizar, text="Salvar Alterações", command=salvar_atualizacao)
        botao_salvar_upd.pack(pady=20)


    botao_adicionar = ctk.CTkButton(frame_botoes, text="Adicionar Produto", command=adicionar_produto)
    botao_adicionar.grid(row=0, column=0, padx=5, pady=5, sticky="ew")

    botao_deletar = ctk.CTkButton(frame_botoes, text="Deletar Produto", command=deletar_produto)
    botao_deletar.grid(row=0, column=1, padx=5, pady=5, sticky="ew")

    botao_atualizar = ctk.CTkButton(frame_botoes, text="Atualizar Produto", command=atualizar_produto_selecionado)
    botao_atualizar.grid(row=0, column=2, padx=5, pady=5, sticky="ew")

    # Função para fechar tudo corretamente
    def fechar_painel_e_app():
        if banco:
            banco.fechar_conexao()
            print("Conexão com o banco fechada (painel).")
        menu_tabela.destroy()
        login_window.destroy() # Destruir a janela de login original que está escondida

    botao_fechar_painel = ctk.CTkButton(frame_botoes, text="Fechar Painel", command=fechar_painel_e_app)
    botao_fechar_painel.grid(row=0, column=3, padx=5, pady=5, sticky="ew")

    menu_tabela.protocol("WM_DELETE_WINDOW", fechar_painel_e_app)
    
    # REMOVA ESTA LINHA: menu_tabela.mainloop()
    # A mainloop da 'janela' (login_window) já está rodando e cuidará dos eventos desta Toplevel.