<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/navbar.php';
?>

<!DOCTYPE html>
<html lang="ru">
<head>
  <meta charset="UTF-8">
  <title>🖥️ Мониторинг серверов</title>
  <link rel="stylesheet" href="../includes/style.css">
</head>
<body>
  <div class="container-server">
    <h1>📊 Дашборд серверов</h1>
    <p class="p-center"><a class="href-center" href="add_server.php">➕ Добавить сервер</a></p>
    <div class="card-grid" id="server-list"></div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <script>
    let chartsData = {};
    let charts = {};
    let currentRange = 1440;

    function renderServers(data) {
      const container = document.getElementById('server-list');
      const savedRanges = {};

      // Сохраняем текущие выбранные значения
      document.querySelectorAll('.range-select').forEach(select => {
        savedRanges[select.dataset.sid] = select.value;
      });

      container.innerHTML = '';

      data.forEach(server => {
        const statusClass = server.status === 'online' ? 'status-online' : 'status-offline';
        const sid = server.id;
        const stored = chartsData[sid] || { cpu: [], memory: [] };
        const selectedRange = savedRanges[sid] || '1440'; // 24 ч по умолчанию

        const div = document.createElement('div');
        div.classList.add('server-card');

        div.innerHTML = `
          <div class="card-top">
            <h3>${server.name}</h3>
            <p><strong>IP:</strong> ${server.ip}</p>
            <p><strong>Статус:</strong> <span class="${statusClass}">${server.status}</span></p>
            <label for="range-${sid}"><strong>Период:</strong></label>
            <select id="range-${sid}" class="range-select" data-sid="${sid}">
              <option value="30" ${selectedRange === '30' ? 'selected' : ''}>30 мин</option>
              <option value="60" ${selectedRange === '60' ? 'selected' : ''}>1 ч</option>
              <option value="240" ${selectedRange === '240' ? 'selected' : ''}>4 ч</option>
              <option value="1440" ${selectedRange === '1440' ? 'selected' : ''}>24 ч</option>
            </select>
            <p><strong>CPU:</strong> <canvas id="cpu-${server.id}" class="metric-chart"></canvas></p>
            <p><strong>Память:</strong> <canvas id="mem-${server.id}" class="metric-chart"></canvas></p>
            <p><strong>Диски:</strong></p>
            <div id="disks-${server.id}"></div>
            <p><strong>Службы:</strong></p>
            <ul id="services-${server.id}"></ul>
          </div>
          <div class="card-bottom">
            <p class="p-center"><a class="href-center" href="edit_server.php?id=${server.id}" class="edit-button">✏️ Редактировать</a></p>
          </div>
        `;

        container.appendChild(div);

        charts[sid] = charts[sid] || {};

        // Удаляем старые графики, если есть
        if (charts[sid].cpu) charts[sid].cpu.destroy();
        if (charts[sid].memory) charts[sid].memory.destroy();

        // Перерисовываем новые
        if (Array.isArray(stored.cpu) && stored.cpu.length) {
          charts[sid].cpu = renderChart(`cpu-${sid}`, stored.cpu, 'CPU Load (%)', 'line', 0, 100);
        }

        if (Array.isArray(stored.memory) && stored.memory.length) {
          charts[sid].memory = renderChart(`mem-${sid}`, stored.memory, 'Memory Usage (MB)', 'line', 0, stored.totalMemory || 8192);
        }

        const disksDiv = document.getElementById(`disks-${server.id}`);
        server.disks.forEach(d => {
          disksDiv.innerHTML += `<p>${d.device} ${d.used} GB / ${d.size} GB</p>`;
        });

        const svcUl = document.getElementById(`services-${server.id}`);
        (server.services || []).forEach(s => {
          const li = document.createElement('li');
          li.textContent = `${s.name}: ${s.status}`;
          svcUl.appendChild(li);
        });
      });
      document.querySelectorAll('.range-select').forEach(select => {
        select.addEventListener('change', async (e) => {
          const sid = e.target.dataset.sid;
          const range = e.target.value;

          currentRange = range;

          try {
            const histRes = await fetch(`history.php?range=${range}&server_id=${sid}`);
            const hist = await histRes.json();

            chartsData[sid].cpu = hist[sid]?.cpu?.slice(-50) || [];
            chartsData[sid].memory = hist[sid]?.memory?.slice(-50) || [];

            const currRes = await fetch('monitor_data.php');
            const curr = await currRes.json();
            const server = curr.find(s => s.id == sid);

            chartsData[sid].totalMemory = server?.memory?.total || 8192;

            if (charts[sid]?.cpu) {
              charts[sid].cpu.destroy();
            }
            if (charts[sid]?.memory) {
              charts[sid].memory.destroy();
            }

            charts[sid].cpu = renderChart(`cpu-${sid}`, chartsData[sid].cpu, 'CPU Load (%)', 'line', 0, 100);
            charts[sid].memory = renderChart(`mem-${sid}`, chartsData[sid].memory, 'Memory Usage (MB)', 'line', 0, chartsData[sid].totalMemory);

          } catch (err) {
            console.error('Ошибка загрузки истории:', err);
          }
        });
      });
    }

    function renderChart(canvasId, dataArray, label, type = 'line', min = 0, max = 100) {
      const canvas = document.getElementById(canvasId);
      if (!canvas) return;

      const ctx = canvas.getContext('2d');
      const timestamps = dataArray.map(p => new Date(p.t * 1000).toLocaleTimeString());
      const values = dataArray.map(p => p.v);

      return new Chart(ctx, {
        type: type,
        data: {
          labels: timestamps,
          datasets: [{
            label: label,
            data: values,
            backgroundColor: type === 'bar' ? 'rgba(100,149,237,0.6)' : 'rgba(54,162,235,0.2)',
            borderColor: 'rgba(54,162,235,1)',
            borderWidth: 1,
            fill: type !== 'bar'
          }]
        },
        options: {
          animation: false,
          scales: {
            y: { min: min, max: max }
          }
        }
      });
    }

    async function updateData() {
      try {
        const currRes = await fetch('monitor_data.php');
        const curr = await currRes.json();

        const histRes = await fetch('history.php?range=' + currentRange);
        const hist = await histRes.json();

        curr.forEach(server => {
          const sid = server.id;

          if (!chartsData[sid]) {
            chartsData[sid] = { cpu: [], memory: [], totalMemory: server.memory.total };
          }

          // Заменяем данные на новые из истории
          chartsData[sid].cpu = hist[sid]?.cpu ?? [];
          chartsData[sid].memory = hist[sid]?.memory ?? [];

          // Обновим общее кол-во памяти, если поменялось
          chartsData[sid].totalMemory = server.memory.total;
        });

        renderServers(curr);
      } catch (e) {
        console.error('Ошибка обновления данных мониторинга:', e);
      }
    }

    updateData();
    setInterval(updateData, 60000);
  </script>
</body>
</html>
