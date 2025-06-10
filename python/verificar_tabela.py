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

    cursor.execute("SHOW TABLES")
    tabelas = cursor.fetchall()

    if ("produtos",) not in tabelas:
        print("\n⚠️  Tabela 'produtos' não existe! Criando agora...\n")
        cursor.execute('''
            CREATE TABLE produtos (
                id INT AUTO_INCREMENT PRIMARY KEY,
                nome VARCHAR(100),
                categoria VARCHAR(100),
                validade DATE,
                preco FLOAT,
                quantidade INT
            )
        ''')
        conn.commit()
        print("✅ Tabela 'produtos' criada com sucesso!")
    else:
        print("✅ Tabela 'produtos' já existe.")

    print("\nConteúdo atual da tabela:")
    cursor.execute("SELECT * FROM produtos")
    for row in cursor.fetchall():
        print(row)

except mariadb.Error as e:
    print(f"Erro de conexão com o banco: {e}")
