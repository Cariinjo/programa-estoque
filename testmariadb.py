import mariadb

try:
    conn = mariadb.connect(
        user="root",
        password="",
        host="localhost",
        port=3306,
        database="banco_py"
    )
    cursor = conn.cursor()

    try:
        nome = input("Digite o nome: ")
        idade = int(input("Digite a idade: "))
        sobrenome = input("Digite o sobrenome: ")
        cidade = input("Digite a cidade: ")

        cursor.execute(
            "INSERT INTO usuarios (nome, idade, sobrenome, cidade) VALUES (?, ?, ?, ?)",
            (nome, idade, sobrenome, cidade)
        )

        conn.commit()
        print("Dados inseridos com sucesso!")

    except mariadb.Error as e:
        print(f"Erro ao inserir dados no MariaDB: {e}")
        conn.rollback()

    finally:
        conn.close()

except mariadb.Error as e:
    print(f"Erro ao conectar ao MariaDB: {e}")
    exit()