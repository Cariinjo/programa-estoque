# Teste: inserir produtos diretamente no banco para validar a tabela do painel
from POO_banco_e_py import Banco

banco = Banco("banco_py")

produtos_exemplo = [
    ("Arroz", "Alimento", "2025-12-31", 19.99, 20),
    ("Detergente", "Limpeza", "2026-06-01", 3.49, 50),
    ("Caderno", "Papelaria", "2027-01-01", 12.00, 30)
]

for p in produtos_exemplo:
    banco.cursor.execute(
        "INSERT INTO produtos (nome, categoria, validade, preco, quantidade) VALUES (?, ?, ?, ?, ?)",
        p
    )

banco.conn.commit()
print("✅ Produtos de exemplo inseridos!")
