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
  <style>
    .card-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
      gap: 20px;
      margin-top: 20px;
    }
    .server-card {
      background: #fff;
      border: 1px solid #ccc;
      border-radius: 8px;
      padding: 15px;
      box-shadow: 0 2px 5px rgba(0,0,0,0.05);
    }
    .server-card h3 {
      margin: 0 0 10px;
    }
    .status-online { color: green; font-weight: bold; }
    .status-offline { color: red; font-weight: bold; }
    .metric-chart { max-height: 150px; width: 100%; margin-bottom: 15px; }
  </style>
</head>
<body>
  <div class="container">
    <h1>📊 Дашборд серверов</h1>
    <div class="card-grid" id="server-list"></div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <script>
    let chartsData = {};
    let charts = {};
    let historyLoaded = false;

    function renderServers(data) {
      const container = document.getElementById('server-list');
      container.innerHTML = '';

      data.forEach(server => {
        const statusClass = server.status === 'online' ? 'status-online' : 'status-offline';
        const sid = server.id;
        const stored = chartsData[sid] || { cpu: [], memory: [] };

        // Формируем div карточки
        const div = document.createElement('div');
        div.classList.add('server-card');
        
        Object.keys(charts).forEach(id => {
          charts[id].destroy();
        });
        charts = {};
      
        div.innerHTML = `
          <h3>${server.name}</h3>
          <p><strong>IP:</strong> ${server.ip}</p>
          <p><strong>Статус:</strong> <span class="${statusClass}">${server.status}</span></p>
          <p><strong>CPU:</strong> <canvas id="cpu-${server.id}" class="metric-chart"></canvas></p>
          <p><strong>Память:</strong> <canvas id="mem-${server.id}" class="metric-chart"></canvas></p>
          <p><strong>Диски:</strong></p>
          <div id="disks-${server.id}"></div>
          <p><strong>Службы:</strong></p>
          <ul id="services-${server.id}"></ul>
        `;
        container.appendChild(div);

        if (Array.isArray(stored.cpu) && stored.cpu.length) {
          renderChart(`cpu-${sid}`, stored.cpu, 'CPU Load (%)', 'line', 0, 100);
        }

        if (Array.isArray(stored.memory) && stored.memory.length) {
          renderChart(`mem-${sid}`, stored.memory, 'Memory Usage (MB)', 'line', 0, stored.totalMemory || 8192);
        }

        // Диски
        const disksDiv = document.getElementById(`disks-${server.id}`);
        server.disks.forEach(d => {
          disksDiv.innerHTML += `<p>${d.device} ${d.used} GB / ${d.size} GB</p>`;
        });

        // Службы
        const svcUl = document.getElementById(`services-${server.id}`);
        server.services.forEach(s => {
          const li = document.createElement('li');
          li.textContent = `${s.name}: ${s.status}`;
          svcUl.appendChild(li);
        });
      });
    }

    function renderChart(canvasId, dataArray, label, type = 'line', min = 0, max = 100) {
      const ctx = document.getElementById(canvasId).getContext('2d');

      const timestamps = dataArray.map(p => new Date(p.t * 1000).toLocaleTimeString());
      const values = dataArray.map(p => p.v);

      if (charts[canvasId]) {
        charts[canvasId].data.labels = timestamps;
        charts[canvasId].data.datasets[0].data = values;
        charts[canvasId].update();
        return;
      }

      charts[canvasId] = new Chart(ctx, {
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

        // Загружаем историю один раз
        if (!historyLoaded) {
          const histRes = await fetch('history.php?range=1440');
          const hist = await histRes.json();

          curr.forEach(server => {
            const sid = server.id;
            chartsData[sid] = {
              cpu: Array.isArray(hist[sid]?.cpu) ? hist[sid].cpu.slice(-50) : [],
              memory: Array.isArray(hist[sid]?.memory) ? hist[sid].memory.slice(-50) : [],
              totalMemory: server.memory.total
            };
          });

          historyLoaded = true;
        }

        curr.forEach(server => {
          const sid = server.id;
          const now = Math.floor(Date.now() / 1000);

          if (!chartsData[sid]) {
            chartsData[sid] = { cpu: [], memory: [], totalMemory: server.memory.total };
          }
          const latestCpu = server.cpu.history?.slice(-1)[0];
          if (latestCpu) {
            chartsData[sid].cpu.push(latestCpu);
          }
          chartsData[sid].memory.push({ t: now, v: server.memory.used });

          chartsData[sid].cpu = chartsData[sid].cpu.slice(-50);
          chartsData[sid].memory = chartsData[sid].memory.slice(-50);
        });

        renderServers(curr);
      } catch(e) {
        console.error(e);
      }
    }

    updateData();
    setInterval(updateData, 60000);
  </script>
</body>
</html>
