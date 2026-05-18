let meuGrafico;

// Bloqueio visual imediato
document.body.style.display = "none";

async function verificarSessao() {
    try {
        const response = await fetch('auth.php?acao=verificar');
        if (!response.ok) {
            window.location.href = 'login.html';
            return;
        }
        const resultado = await response.json();
        if (resultado.logado === true) {
            document.body.style.display = "block";
            const saudacao = document.getElementById('nomeUsuario');
            if(saudacao) saudacao.innerText = resultado.nome;
            carregarGastos(); 
        } else {
            window.location.href = 'login.html';
        }
    } catch (erro) {
        console.error("Erro técnico:", erro);
        document.body.style.display = "block"; 
    }
}

verificarSessao();

const formatarMoeda = (valor) => {
    return parseFloat(valor).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
};

async function carregarGastos(filtros = "") {
    if (typeof filtros !== 'string') filtros = "";
    try {
        const response = await fetch(`gastos.php${filtros}`);
        if (!response.ok) throw new Error(`Erro na API: ${response.status}`);
        const gastos = await response.json();
        renderizarTabelaETotais(gastos);
    } catch (erro) {
        console.error("Erro ao buscar dados:", erro);
    }
}

function renderizarTabelaETotais(gastos) {
    const tabela = document.getElementById('listaGastos');
    const displayTotal = document.getElementById('totalGastos');
    if(!tabela) return;

    tabela.innerHTML = '';
    let totalGeral = 0;
    const dadosGrafico = {};

    if (gastos.length === 0) {
        tabela.innerHTML = '<tr><td colspan="6" class="text-center">Nenhum gasto encontrado.</td></tr>';
    }

    gastos.forEach(gasto => {
        const valor = parseFloat(gasto.valor);
        totalGeral += valor;
        dadosGrafico[gasto.categoria_nome] = (dadosGrafico[gasto.categoria_nome] || 0) + valor;

        tabela.innerHTML += `
            <tr>
                <td>${new Date(gasto.data_movimentacao + 'T00:00:00').toLocaleDateString('pt-BR')}</td>
                <td>${gasto.descricao}</td>
                <td><span class="badge bg-secondary">${gasto.categoria_nome}</span></td>
                <td class="text-danger fw-bold">${formatarMoeda(valor)}</td>
                <td>
                    <button onclick='prepararEdicao(${JSON.stringify(gasto)})' class="btn btn-sm btn-outline-warning">Editar</button>
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
    if(!canvas || typeof Chart === 'undefined') return;
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

// Evento do Formulário de Cadastro
const formGasto = document.getElementById('formGasto');
if (formGasto) {
    formGasto.addEventListener('submit', async (e) => {
        e.preventDefault();

        // Aqui pegamos APENAS os dados do formulário de cadastro novo!
        const dados = {
            descricao: document.getElementById('descricao').value,
            valor: document.getElementById('valor').value,
            categoria_id: document.getElementById('categoria_id').value,
            data: new Date().toISOString().split('T')[0] // Captura a data de hoje automaticamente
        };

        try {
            const response = await fetch('gastos.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(dados)
            });

            const resultado = await response.json();

            if (response.ok) {
                formGasto.reset(); // Limpa os campos do formulário
                carregarGastos();  // Recarrega a tabela e o gráfico automaticamente
                alert("Gasto cadastrado com sucesso!");
            } else {
                alert("Erro ao cadastrar: " + (resultado.msg || "Erro interno"));
            }
        } catch (erro) {
            console.error("Erro na requisição POST:", erro);
        }
    });
}

// Funções de Edição (MODAL)
function prepararEdicao(gasto) {
    document.getElementById('edit_id').value = gasto.id;
    document.getElementById('edit_data').value = gasto.data_movimentacao;
    document.getElementById('edit_descricao').value = gasto.descricao;
    document.getElementById('edit_valor').value = gasto.valor;
    document.getElementById('edit_categoria').value = gasto.categoria_id; // Ajustado para edit_categoria

    const modalEl = document.getElementById('modalEdicao');
    const modal = new bootstrap.Modal(modalEl);
    modal.show();
}

// Evento para salvar a edição
const formEdicao = document.getElementById('formEdicao');
if (formEdicao) {
    formEdicao.addEventListener('submit', async (e) => {
        e.preventDefault();

        // Captura os dados exatamente como estão no HTML do Modal
        const dados = {
            id: document.getElementById('edit_id').value,
            data: document.getElementById('edit_data').value, 
            descricao: document.getElementById('edit_descricao').value,
            valor: document.getElementById('edit_valor').value,
            categoria_id: document.getElementById('edit_categoria').value // Garanta que bate com o ID do HTML abaixo
        };

        try {
            const response = await fetch('gastos.php', {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(dados)
            });

            const resultado = await response.json();

            if (response.ok) {
                // Fecha o modal do Bootstrap com segurança
                const modalEl = document.getElementById('modalEdicao');
                const modalInstance = bootstrap.Modal.getInstance(modalEl);
                if (modalInstance) modalInstance.hide();
                
                carregarGastos(); // Recarrega a tabela e o gráfico com os novos dados
                alert("Atualizado com sucesso!");
            } else {
                alert("Erro ao atualizar: " + (resultado.erro || "Erro desconhecido"));
            }
        } catch (erro) {
            console.error("Erro na requisição PUT:", erro);
        }
    });
}

async function excluirGasto(id) {
    if (confirm("Deseja excluir?")) {
        await fetch(`gastos.php?id=${id}`, { method: 'DELETE' });
        carregarGastos();
    }
}

function carregarGastosComFiltro() {
    const inicio = document.getElementById('filtro_inicio').value;
    const fim = document.getElementById('filtro_fim').value;
    if (inicio && fim) carregarGastos(`?inicio=${inicio}&fim=${fim}`);
}

async function logout() {
    await fetch('auth.php?acao=logout');
    window.location.href = 'login.html';
}