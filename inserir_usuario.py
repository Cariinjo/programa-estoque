class Pessoa:
    def __init__(self, nome, idade, sobrenome, cidade, senha):
        self.nome = nome
        self.idade = idade
        self.sobrenome = sobrenome
        self.cidade = cidade
        self.senha = senha

    def inserir_no_banco(self, conexao):
        try:
            conexao.cursor.execute(
                "INSERT INTO usuarios (nome, idade, sobrenome, cidade, senha) VALUES (?, ?, ?, ?, ?)",
                (self.nome, self.idade, self.sobrenome, self.cidade, self.senha)
            )
            conexao.conn.commit()
            print("Usuário inserido com sucesso!\n")
        except mariadb.Error as e:
            print(f"Erro ao inserir usuário '{self.nome}': {e}")
