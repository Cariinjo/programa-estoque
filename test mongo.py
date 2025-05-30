from pymongo import MongoClient
from pymongo.errors import PyMongoError, DuplicateKeyError

try:
    # Conexão com o MongoDB
    client = MongoClient("mongodb://localhost:27017/")
    db = client["banco_py"]
    collection = db["usuarios"]

    # Dados do usuário
    nome = input("Digite o nome: ")
    idade = int(input("Digite a idade: "))
    sobrenome = input("Digite o sobrenome: ")
    cidade = input("Digite a cidade: ")

    usuario = {
        "nome": nome,
        "idade": idade,
        "sobrenome": sobrenome,
        "cidade": cidade
    }

    # Tenta inserir
    collection.insert_one(usuario)
    print("✅ Dados inseridos com sucesso!")

except DuplicateKeyError:
    print("❌ Erro: já existe um registro com esse _id!")

except PyMongoError as erro:
    print(f"❌ Erro ao inserir no MongoDB: {erro}")


A = input("digite o nome para buscar: ")

for doc in collection.find({"nome": A }):
    print(doc)
