import mariadb

class pessoas:
    def __init__(self,nome,idade,sobrenome,cidade):
        self.nome=nome
        self.idade=idade
        self.sobrenome=sobrenome
        self.cidade=cidade

    def inserir_no_banco(self,batata):
        try:
            batata.cursor.execute(
                "insert into usuarios(nome,idade,sobrenome,cidade) values (?,?,?,?)",
                (self.nome, self.idade, self.sobrenome, self.cidade)
            )
            batata.conn.commit()
            print ("usuario inserido com sucesso!\n")
        except mariadb.Error as e:
            print("Erro ao inserir usuario:", e)
