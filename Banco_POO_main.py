import mariadb
import POO_banco_e_py
import inserir_usuario
from inserir_usuario import pessoas
from funcao_usuario import listar_usuarios
from funcao_usuario import buscar_nome
from funcao_usuario import deletar
from funcao_usuario import atualizar

conectar = POO_banco_e_py.Banco("banco_py")
while True:
    print("\n")
    print("BEM VINDO AO BANCO DE DADOS\n")
    print("Digite 1 para adicionar mais uma pessoa: ")
    print("Digite 2 para pesquisar pessoa: ")
    print("Digite 3 para mostrar todos os usuarios: ")
    print("Digite 4 para deletar usuario: ")
    print("Digite 5 para atualizar usuario: ")
    print("Digite 6 para sair: \n")
    n=int(input("Digite a opcao desejada: \n"))
    if n==1:
        nome=input("digite o nome: ")
        idade=int(input("digite a idade: "))
        sobrenome=input("digite o sobrenome: ")
        cidade=input("digite a cidade: ")
        inserir=pessoas(nome,idade,sobrenome,cidade)
        inserir.inserir_no_banco(conectar)

    elif n==2:
        buscar_nome(conectar)

    elif n==3:
        listar_usuarios(conectar)

    elif n==4:
        deletar(conectar)

    elif n==5:
        atualizar(conectar)

    elif n==6:
        conectar.fechar_conexao()
        print("MUITO OBRIGADO POR USAR O BANCO DE DADOS")
        break
