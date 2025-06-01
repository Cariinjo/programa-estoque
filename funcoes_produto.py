class Produto:
    def __init__(self, nome, categoria, validade, preco, quantidade):
        self.nome = nome
        self.categoria = categoria
        self.validade = validade
        self.preco = preco
        self.quantidade = quantidade

    def inserir_no_banco(self, conexao):
        try:
            conexao.cursor.execute(
                "INSERT INTO produtos (nome, categoria, validade, preco, quantidade) VALUES (?, ?, ?, ?, ?)",
                (self.nome, self.categoria, self.validade, self.preco, self.quantidade)
            )
            conexao.conn.commit()
            print("Produto inserido com sucesso!\n")
        except Exception as e:
            print(f"Erro ao inserir produto '{self.nome}': {e}")
            
            
def listar_produtos(cursor, label):
    cursor.execute("SELECT * FROM produtos")
    produtos = cursor.fetchall()
    if produtos:
        texto = "Produtos cadastrados:\n\n"
        for p in produtos:
            texto += f"{p[1]} - {p[2]} - {p[4]} reais - {p[5]} unidades\n"
    else:
        texto = "Nenhum produto encontrado."
    label.configure(text=texto)

def buscar_nome(conexao):
    nome = input("Digite o nome do produto: ")
    conexao.cursor.execute("SELECT * FROM produtos WHERE nome = ?", (nome,))
    print(conexao.cursor.fetchall())

def deletar(conexao):
    nome = input("Digite o nome do produto a deletar: ")
    conexao.cursor.execute("DELETE FROM produtos WHERE nome = ?", (nome,))
    conexao.conn.commit()
    print("Produto deletado com sucesso!")

def atualizar(conexao):
    nome = input("Digite o nome do produto a atualizar: ")
    conexao.cursor.execute("SELECT * FROM produtos WHERE nome = ?", (nome,))
    if conexao.cursor.fetchone():
        campo = input("Digite o campo a ser alterado (nome, categoria, validade, preco, quantidade): ")
        novo_valor = input("Digite o novo valor: ")
        conexao.cursor.execute(f"UPDATE produtos SET {campo} = ? WHERE nome = ?", (novo_valor, nome))
        conexao.conn.commit()
        print("Produto atualizado com sucesso!")
    else:
        print("Produto não encontrado.")
