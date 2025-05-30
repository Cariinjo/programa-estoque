import mariadb
import POO_banco_e_py
#verdadeiro
'''def listar_usuarios(self):
    self.cursor.execute("SELECT * FROM usuarios")
    usuarios = self.cursor.fetchall()
    if usuarios:
        print("Usuarios cadastrados: \n")
        for usuario in usuarios:
            print(usuario)
    else:
        print("Nenhum usuario encontrado.\n")'''


# funcao_usuario.py
def listar_usuarios(cursor, label_usuarios):
    cursor.execute("SELECT * FROM usuarios")
    usuarios = cursor.fetchall()

    if usuarios:
        resultado = "Usuários cadastrados:\n\n"
        for usuario in usuarios:
            resultado += f"{usuario[1]}\n"
    else:
        resultado = "Nenhum usuário encontrado.\n"

    label_usuarios.config(text=resultado)





def buscar_nome(self):
    buscar_nome=input("digite o nome: \n")
    self.cursor.execute("SELECT * FROM usuarios where nome = ?",(buscar_nome,))
    usuarios = self.cursor.fetchall()
    print (usuarios)

def deletar(self):
    nome_deletar = input("Digite o nome para deletar: \n")
    sobrenome_deletar=input(f"Digite o sobrenome do {nome_deletar} para deletar: \n")
    self.cursor.execute("SELECT * FROM usuarios WHERE nome = ? and sobrenome = ?", (nome_deletar,sobrenome_deletar))
    usuario = self.cursor.fetchone()

    if usuario:
        self.cursor.execute("DELETE FROM usuarios WHERE nome = ? and sobrenome = ?", (nome_deletar,sobrenome_deletar))
        self.conn.commit()
        print(f"Usuário '{nome_deletar}, {sobrenome_deletar}' deletado com sucesso.\n")
    else:
        print(f"Usuário '{nome_deletar}, {sobrenome_deletar}' não encontrado.\n")

def atualizar(self):
    nome_usuario_atualizar = input("Digite o nome do usuário a ser atualizado: \n")
    sobrenome_usuario_atualizar = input(f"Digite o sobrenome do {nome_usuario_atualizar} a ser atualizado: \n")
    self.cursor.execute("SELECT * FROM usuarios WHERE nome = ? and sobrenome = ?", (nome_usuario_atualizar,sobrenome_usuario_atualizar))
    usuario = self.cursor.fetchall()
    if usuario:
        print(f"o usuario encontrado foi: {usuario}\n")
        id_mudar = input("Digite o ID do usuário que deseja mudar: \n")
        item_atualizar = input("Digite o item que deseja atualizar: \n")
        item_novo = input(f"Digite o novo valor para o item {item_atualizar} : \n")
        self.cursor.execute(f"UPDATE usuarios SET {item_atualizar} = ? WHERE id = ?", (item_novo, id_mudar))
        self.conn.commit()

    else:
        print(f"Usuário '{nome_usuario_atualizar}, {sobrenome_usuario_atualizar}' não encontrado.\n")




