

<div id="orcamentoModal" class="modal">
    <div class="modal-content">
        <span class="close-button">&times;</span>
        <h2>Solicitar Orçamento</h2>
        <p>Preencha os detalhes para solicitar um orçamento para o serviço: <strong id="modalServicoTitulo"></strong></p>
        <form id="orcamentoForm" >
            <input type="hidden" id="modalServicoId" name="servico_id">
            <div class="form-group">
                <label for="descricaoOrcamento">Descreva o que você precisa:</label>
                <textarea id="descricaoOrcamento" name="descricao" rows="5" required></textarea>
            </div>
            <button type="submit" class="btn btn-primary">Enviar Solicitação</button>
            <div id="orcamentoMessage" class="message-area"></div>
        </form>
    </div>
</div>

<style>
/* Modal */
.modal {
    display: none; /* Hidden by default */
    position: fixed; /* Stay in place */
    z-index: 1000; /* Sit on top */
    left: 0;
    top: 0;
    width: 100%; /* Full width */
    height: 100%; /* Full height */
    overflow: auto; /* Enable scroll if needed */
    background-color: rgba(0,0,0,0.6); /* Black w/ opacity */
    padding-top: 60px;
}

.modal-content {
    background-color: #fefefe;
    margin: 5% auto; /* 15% from the top and centered */
    padding: 30px;
    border-radius: 15px;
    box-shadow: 0 5px 20px rgba(0,0,0,0.2);
    width: 90%; /* Could be more or less, depending on screen size */
    max-width: 600px;
    position: relative;
    animation: fadeIn 0.5s;
}

.close-button {
    color: #aaa;
    float: right;
    font-size: 28px;
    font-weight: bold;
    position: absolute;
    top: 15px;
    right: 25px;
}

.close-button:hover,
.close-button:focus {
    color: black;
    text-decoration: none;
    cursor: pointer;
}

.modal h2 {
    color: #6c5ce7;
    margin-top: 0;
    margin-bottom: 1rem;
    font-size: 1.8rem;
}

.modal p {
    color: #636e72;
    margin-bottom: 1.5rem;
}

.modal .form-group {
    margin-bottom: 1rem;
}

.modal .form-group label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: bold;
    color: #2d3436;
}

.modal .form-group textarea {
    width: calc(100% - 20px);
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 8px;
    font-size: 1rem;
    resize: vertical;
    min-height: 100px;
}

.modal .btn-primary {
    background: linear-gradient(45deg, #6c5ce7, #a29bfe);
    color: white;
    padding: 10px 20px;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-size: 1rem;
    transition: transform 0.3s ease;
    width: 100%;
}

.modal .btn-primary:hover {
    transform: translateY(-2px);
}

.message-area {
    margin-top: 1rem;
    padding: 10px;
    border-radius: 8px;
    text-align: center;
}

.message-area.success {
    background-color: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

.message-area.error {
    background-color: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

@keyframes fadeIn {
    from {opacity: 0; transform: translateY(-20px);}
    to {opacity: 1; transform: translateY(0);}
}
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const modal = document.getElementById('orcamentoModal');
        const closeButton = document.querySelector('.close-button');
        const orcamentoForm = document.getElementById('orcamentoForm');
        const modalServicoId = document.getElementById('modalServicoId');
        const modalServicoTitulo = document.getElementById('modalServicoTitulo');
        const descricaoOrcamento = document.getElementById('descricaoOrcamento');
        const orcamentoMessage = document.getElementById('orcamentoMessage');

        // Função para abrir o modal
        window.solicitarOrcamento = function(servicoId, servicoTitulo) {
            modalServicoId.value = servicoId;
            modalServicoTitulo.textContent = servicoTitulo;
            descricaoOrcamento.value = ''; // Limpa o textarea
            orcamentoMessage.textContent = ''; // Limpa mensagens anteriores
            orcamentoMessage.className = 'message-area'; // Reseta classes
            modal.style.display = 'block';
        };

        // Função para fechar o modal
        closeButton.onclick = function() {
            modal.style.display = 'none';
        };

        // Fechar modal clicando fora
        window.onclick = function(event) {
            if (event.target == modal) {
                modal.style.display = 'none';
            }
        };

        // Enviar formulário de orçamento
        orcamentoForm.addEventListener('submit', function(e) {
            e.preventDefault();

            const servico_id = modalServicoId.value;
            const descricao = descricaoOrcamento.value;

           fetch('api/solicitar-orcamento.php', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
    },
    body: JSON.stringify({
        id_servico: servico_id,
        detalhes_solicitacao: descricao
    })
})
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    orcamentoMessage.textContent = data.message;
                    orcamentoMessage.classList.add('success');
                    // Opcional: fechar modal após alguns segundos ou redirecionar
                    setTimeout(() => {
                        modal.style.display = 'none';
                        // Redirecionar para meus orçamentos ou dashboard
                        window.location.href = 'meus-orcamentos.php'; 
                    }, 2000);
                } else {
                    orcamentoMessage.textContent = data.error;
                    orcamentoMessage.classList.add('error');
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                orcamentoMessage.textContent = 'Erro ao enviar solicitação. Tente novamente.';
                orcamentoMessage.classList.add('error');
            });
        });
    });
</script>


