let meuGrafico;

// Bloqueio visual imediato: esconde o HTML até o PHP confirmar quem é você
document.body.style.display = "none";

async function verificarSessao() {
    try {
        const response = await fetch('auth.php?acao=verificar');
        
        // Se o servidor der erro (404, 500), manda pro login
        if (!response.ok) {
            window.location.href = 'login.html';
            return;
        }

        const resultado = await response.json();

        if (resultado.logado === true) {
            // SUCESSO: Mostra a página e carrega os dados
            document.body.style.display = "block";
            const saudacao = document.getElementById('nomeUsuario');
            if(saudacao) saudacao.innerText = resultado.nome;
            
            carregarGastos(); 
        } else {
            // NÃO LOGADO: Expulsa
            window.location.href = 'login.html';
        }
    } catch (erro) {
        console.error("Erro técnico:", erro);
        // CASO DE ERRO: Se você estiver no localhost, vamos mostrar a página 
        // para você conseguir debugar, mas no futuro isso deve redirecionar.
        document.body.style.display = "block"; 
        alert("Erro ao verificar sessão. Verifique o console (F12).");
    }
}

// Chame a função imediatamente
verificarSessao();

// Função para formatar moeda
const formatarMoeda = (valor) => {
    return parseFloat(valor).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
};

// 1. Altere o início do arquivo para garantir que a variável 'filtros' comece vazia
async function carregarGastos(filtros = "") {
    
    // Se por acaso 'filtros' receber um evento (o que causa o [object Event])
    // nós limpamos ele para evitar o erro 404
    if (typeof filtros !== 'string') {
        filtros = "";
    }

    console.log("Chamando API no caminho: gastos.php" + filtros);

    try {
        const response = await fetch(`gastos.php${filtros}`);
        
        if (!response.ok) {
            throw new Error(`Erro na API: ${response.status}`);
        }

        const gastos = await response.json();
        renderizarTabelaETotais(gastos);
    } catch (erro) {
        console.error("Erro ao buscar dados:", erro);
    }
}

// 2. Procure onde você chama a função no final do arquivo e garanta que seja assim:
window.onload = () => carregarGastos("");

function renderizarTabelaETotais(gastos) {
    const tabela = document.getElementById('listaGastos');
    const displayTotal = document.getElementById('totalGastos');
    
    if(!tabela) return; // Segurança caso o ID esteja errado

    tabela.innerHTML = '';
    let totalGeral = 0;
    const dadosGrafico = {};

    if (gastos.length === 0) {
        tabela.innerHTML = '<tr><td colspan="5" class="text-center">Nenhum gasto encontrado.</td></tr>';
    }

    gastos.forEach(gasto => {
        const valor = parseFloat(gasto.valor);
        totalGeral += valor;
        dadosGrafico[gasto.categoria_nome] = (dadosGrafico[gasto.categoria_nome] || 0) + valor;

        tabela.innerHTML += `
            <tr>
                <td>${new Date(gasto.data_movimentacao).toLocaleDateString('pt-BR')}</td>
                <td>${gasto.descricao}</td>
                <td><span class="badge bg-secondary">${gasto.categoria_nome}</span></td>
                <td class="text-danger fw-bold">${formatarMoeda(valor)}</td>
                <td>
                    <button onclick="excluirGasto(${gasto.id})" class="btn btn-sm btn-outline-danger">Excluir</button>
                </td>
            </tr>
        `;
    });

    displayTotal.innerText = formatarMoeda(totalGeral);
    desenharGrafico(dadosGrafico);
}

function desenharGrafico(dados) {
    const canvas = document.getElementById('graficoGastos');
    if(!canvas) return;
    const ctx = canvas.getContext('2d');
    
    if (meuGrafico) meuGrafico.destroy();

    meuGrafico = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: Object.keys(dados),
            datasets: [{
                label: 'R$ por Categoria',
                data: Object.values(dados),
                backgroundColor: ['#dc3545', '#ffc107', '#0dcaf0', '#6610f2'],
                borderRadius: 5
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } }
        }
    });
}

// Evento do Formulário
document.getElementById('formGasto').addEventListener('submit', async (e) => {
    e.preventDefault();
    console.log("Enviando novo gasto...");

    const dados = {
        descricao: document.getElementById('descricao').value,
        valor: document.getElementById('valor').value,
        categoria_id: document.getElementById('categoria_id').value,
        data: new Date().toISOString().split('T')[0]
    };

    const response = await fetch('gastos.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(dados)
    });

    if (response.ok) {
        document.getElementById('formGasto').reset();
        carregarGastos();
    }
});

// Funções de Filtro
function carregarGastosComFiltro() {
    const inicio = document.getElementById('filtro_inicio').value;
    const fim = document.getElementById('filtro_fim').value;
    if (inicio && fim) carregarGastos(`?inicio=${inicio}&fim=${fim}`);
}

function setarPeriodo(tipo) {
    const hoje = new Date();
    let inicio = new Date();
    if (tipo === 'semana') inicio.setDate(hoje.getDate() - hoje.getDay());
    else if (tipo === 'mes') inicio = new Date(hoje.getFullYear(), hoje.getMonth(), 1);

    document.getElementById('filtro_inicio').value = inicio.toISOString().split('T')[0];
    document.getElementById('filtro_fim').value = hoje.toISOString().split('T')[0];
    carregarGastosComFiltro();
}

async function excluirGasto(id) {
    if (confirm("Deseja excluir?")) {
        await fetch(`gastos.php?id=${id}`, { method: 'DELETE' });
        carregarGastos();
    }
}
async function logout() {
    // Você precisaria criar a ação 'logout' no auth.php que faz session_destroy()
    await fetch('auth.php?acao=logout');
    window.location.href = 'login.html';
}
