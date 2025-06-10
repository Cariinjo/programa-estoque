import mariadb
class Banco:
    def __init__(self,dados):
        try:
            self.dados = dados
            self.conn = mariadb.connect(
                user="root",
                password="",
                host="localhost",
                port=3306,
                database=dados
            )
            self.cursor = self.conn.cursor()
        except mariadb.Error as e:
            print(f"Erro ao conectar ao banco de dados: {e}")
            exit()

    def fechar_conexao(self):
        self.conn.close()

