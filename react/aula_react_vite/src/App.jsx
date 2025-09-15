import { useState } from 'react'
import './App.css'


/*function Lista() {
  const [pedido, setPedido] = useState(""); // texto do input
  const [lista, setLista] = useState([]);   // lista de pedidos

  function adicionarPedido() {
    const texto = pedido.trim();
    if (texto === "") {
      alert("Digite um pedido antes de adicionar");
      return;
    }

    // adiciona novo pedido à lista
    setLista([...lista, texto]);
    setPedido(""); // limpa o input
  }

  return (
    <div className="p-4">
      <h1>Lista de Pedidos</h1>

      <input
        type="text"
        value={pedido}
        onChange={(e) => setPedido(e.target.value)}
        placeholder="Digite seu pedido"
        className="border p-2 mr-2"
      />

      <button onClick={adicionarPedido} className="bg-blue-500 text-white p-2 rounded">
        Adicionar
      </button>

      <ul className="mt-4">
        {lista.map((item, index) => (
          <li key={index} className="p-1 border-b">
            {item}
          </li>
        ))}
      </ul>
    </div>
  );
}*/

function Aplicativo() {
  const [agendamento, setAgendamento] = useState({
    cliente: "",
    barbeiro: "",
    servico: "",
    local: "",
    data: ""
  });

  const [agendamentos, setAgendamentos] = useState([]);

  const barbeiro = [
    { id: 1, nome: "João" },
    { id: 2, nome: "Cleiton" },
    { id: 3, nome: "Maria" }
  ];

  const servico = [
    { id: 1, nome: "Barba", preco: "R$ 30,00", duracao: "30 minutos" },
    { id: 2, nome: "Corte do Jaca", preco: "R$ 50,00", duracao: "60 minutos" },
    { id: 3, nome: "Pezinho", preco: "R$ 20,00", duracao: "20 minutos" }
  ];

  const local = [
    { id: 1, nome: "Dom bosco"},
    { id: 2, nome: "Tijuco"},
    { id: 3, nome: "Centro" }
  ];

  const atualizarCampo = (campo, valor) => {
    setAgendamento((prev) => ({
      ...prev,
      [campo]: valor
    }));
  };

  const enviarFormulario = (e) => {
    e.preventDefault();

    if (!agendamento.cliente || !agendamento.barbeiro || !agendamento.servico || !agendamento.local || !agendamento.data) {
      alert("Preencha todos os campos!");
      return;
    };

    const novo = {
      ...agendamento,
      id: Date.now(),
      barbeiroDetalhes: barbeiro.find((b) => b.id === agendamento.barbeiro),
      servicosDetalhes: servico.find((s) => s.id === agendamento.servico),
      localDetalhes: local.find((l) => l.id === agendamento.local)
    };

    setAgendamentos((prev) => [...prev, novo]);
    setAgendamento({ cliente: "", barbeiro: "", servico: "",local:"", data: ""});

    alert("Agendamento realizado com sucesso!");

  };

  const cancelarAgendamento = (id) => {
    setAgendamentos((prev) => prev.filter((a) => a.id !== id));
  };



  return (
    <div className="p-4">
      <h1 className="text-2xl mb-4">Sistema de Agendamento de Barbearia</h1>

      <form onSubmit={enviarFormulario} className="space-y-4">
        <div>
          <label className="block mb-1">Nome do Cliente:</label>
          <input
            type="text"
            value={agendamento.cliente}
            onChange={(e) => atualizarCampo("cliente", e.target.value)}
            className="w-full border p-2 rounded"
          />
        </div>

        <div>
          <label className="block mb-1">Selecione o Barbeiro:</label>
          <select
            value={agendamento.barbeiro}
            onChange={(e) => atualizarCampo("barbeiro", Number(e.target.value))}
            className="w-full border p-2 rounded"
          >
            <option value="">-- Selecione --</option>
            {barbeiro.map((b) => (
              <option key={b.id} value={b.id}>
                {b.nome}
              </option>
            ))}
          </select>
        </div>

        <div>
          <label className="block mb-1">Selecione o Serviço:</label>
          <select
            value={agendamento.servico}
            onChange={(e) => atualizarCampo("servico", Number(e.target.value))}
            className="w-full border p-2 rounded"
          >
            <option value="">-- Selecione --</option>
            {servico.map((s) => (
              <option key={s.id} value={s.id}>
                {s.nome} - {s.preco} ({s.duracao})
              </option>
            ))}
          </select>
        </div>

        <div>
          <label className="block mb-1">Selecione o Local:</label>
          <select
            value={agendamento.local}
            onChange={(e) => atualizarCampo("local", Number(e.target.value))}
            className="w-full border p-2 rounded"
          >
            <option value="">-- Selecione --</option>
            {local.map((l) => (
              <option key={l.id} value={l.id}>
                {l.nome}
              </option>
            ))}
          </select>
        </div>

        <div>
          <label className="block mb-1" htmlFor="data">Selecione a data:</label>
          <input
            type="date"
            id="data"
            value={agendamento.data}
            onChange={(e) => atualizarCampo("data", e.target.value)}
            className="w-full border p-2 rounded"
          />
        </div>



        <button type="submit" className="bg-blue-500 text-white p-2 rounded">
          Agendar
        </button>
      </form>

      <div className="mt-6">
        <h2 className="text-xl mb-2">Agendamentos:</h2>
        {agendamentos.length === 0 ? (
          <p>Nenhum agendamento realizado.</p>
        ) : (
          <ul className="space-y-2">
            {agendamentos.map((a) => (
              <li key={a.id} className="border p-2 rounded">
                <p>
                  <strong>Cliente:</strong> {a.cliente}
                </p>
                <p>
                  <strong>Barbeiro:</strong> {a.barbeiroDetalhes.nome}
                </p>
                <p>
                  <strong>Serviço:</strong> {a.servicosDetalhes.nome} -{" "}
                  {a.servicosDetalhes.preco} ({a.servicosDetalhes.duracao})
                </p>
                <p>
                  <strong>Local:</strong> {a.localDetalhes.nome}
                </p>
                <p>
                  <strong>Data:</strong> {a.data}
                </p>
                <button
                  onClick={() => cancelarAgendamento(a.id)}
                  className="bg-red-500 text-white p-1 rounded mt-2"
                >
                  Cancelar Agendamento
                </button>
              </li>
            ))}
          </ul>
        )}
      </div>
    </div>
  );
}

export default Aplicativo;

