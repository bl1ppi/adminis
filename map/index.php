<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/navbar.php';
?>

<!DOCTYPE html>
<html lang="ru">
  <head>
      <meta charset="UTF-8">
      <title>Карта сети</title>
      <!-- Bootstrap 5 CSS -->
      <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
      <script src="https://unpkg.com/gojs/release/go.js"></script>
      <script src="https://unpkg.com/pdfkit/js/pdfkit.standalone.js"></script>
      <script src="https://unpkg.com/blob-stream"></script>
      <script src="https://unpkg.com/svg-to-pdfkit/source.js"></script>
      <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
      <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
      <style>
        html, body { margin: 0; padding: 0; height: 100%; font-family: sans-serif; }
        .layout-wrapper {
            display: flex;
        }
        .sidebar {
            min-width: 250px;
            max-width: 250px;
            padding: 20px;
            border-right: 1px solid #dee2e6;
        }
        .diagram-container { flex-grow: 1; }
        .sidebar label {
          font-weight: bold;
          margin-top: 10px;
          display: block;
        }
        .sidebar input, .sidebar select {
          width: 100%;
          margin-bottom: 10px;
        }
        #myDiagramDiv {
          width: 100%;
          height: 100%;
        }
        #layoutSettings {
          display: grid;
          grid-template-columns: auto 1fr;
          gap: 5px 10px;
          align-items: center;
        }
        #layoutSettings label {
          margin: 0;
          font-weight: normal;
        }
        #layoutSettings input,
        #layoutSettings select {
          margin-bottom: 0;
        }
      </style>
  </head>

<body>
<div class="layout-wrapper">
  <div class="sidebar min-vh-100 bg-light p-3 border-end">
    <form>
      <h5 class="mb-3">⚙️ Настройки схемы</h5>

      <div class="mb-3">
        <label for="layoutType" class="form-label">Тип раскладки:</label>
        <select id="layoutType" class="form-select">
          <option value="GridLayout">Сетка (Grid)</option>
          <option value="LayeredDigraphLayout" selected>Слоистая (Layered)</option>
          <option value="ForceDirectedLayout">Силовая (Force)</option>
        </select>
      </div>

      <div id="layoutSettings" class="mb-3"></div>

      <button type="button" class="btn btn-outline-primary w-100">
        📄 Скачать PDF<br><small>(вся схема)</small>
      </button>
    </form>
  </div>

  <div class="content p-0" style="flex-grow:1;">
    <div id="myDiagramDiv" style="width: 100%; height: 100vh;"></div>
  </div>
</div>

<script>
const $ = go.GraphObject.make;

const diagram = $(go.Diagram, "myDiagramDiv", {
  initialContentAlignment: go.Spot.Left,
  "undoManager.isEnabled": true,
  "linkingTool.direction": go.LinkingTool.ForwardsOnly,
  "linkTemplate.zOrder": 0,
  "model": new go.GraphLinksModel()
});

// Группы
diagram.groupTemplate =
  $(go.Group, "Auto",
    { zOrder: -2 },
    $(go.Shape, "RoundedRectangle", { fill: "#f0f0f0", stroke: "#ccc" }),
    $(go.Panel, "Vertical",
      $(go.TextBlock, { margin: 6, font: "bold 14px sans-serif" }, new go.Binding("text")),
      $(go.Placeholder, { padding: 10 })
    )
  );

// Узлы
diagram.nodeTemplate =
  $(go.Node, "Vertical",
    { zOrder: 2, movable: true },
    $(go.Picture, {
      width: 48, height: 48,
      margin: 2
    }, new go.Binding("source", "icon")),
    $(go.TextBlock, {
      margin: 2,
      font: "12px sans-serif",
      wrap: go.TextBlock.WrapFit,
      width: 100,
      textAlign: "center"
    }, new go.Binding("text"))
  );

// Связи
diagram.linkTemplate =
  $(go.Link,
    {
      routing: go.Link.AvoidsNodes,
      curve: go.Link.JumpOver,
      corner: 5,
      zOrder: -1
    },
    $(go.Shape, { strokeWidth: 1.5 }),
    $(go.Shape, { toArrow: "Standard" })
  );

// Двойной клик
diagram.addDiagramListener("ObjectDoubleClicked", function (e) {
  const node = e.subject.part;
  if (!node || !node.data || !node.data.key) return;

  const key = node.data.key;

  if (typeof key === "number") {
    window.location.href = "../rooms/edit_device.php?id=" + key;
  } else if (typeof key === "string" && key.startsWith("room_")) {
    const roomId = key.split("_")[1];
    window.location.href = "../rooms/index.php?id=" + roomId;
  }
});

diagram.makeSvg({
  scale: 1,
  background: "white"
});

// ---- Динамическая смена раскладки ----

const layoutSettingsContainer = document.getElementById('layoutSettings');

const layoutParams = {
  GridLayout: {
    wrappingColumn: 3,
    spacing: 20
  },
  LayeredDigraphLayout: {
    direction: 90,
    layerSpacing: 50,
    columnSpacing: 30
  },
  ForceDirectedLayout: {
    defaultSpringLength: 50,
    defaultElectricalCharge: 150
  }
};

fetch("map_data.php")
  .then(response => response.json())
  .then(data => {
    diagram.model = new go.GraphLinksModel({
      nodeDataArray: data.nodes,
      linkDataArray: data.edges
    });
  });

function renderLayoutForm(type) {
  const params = layoutParams[type];
  layoutSettingsContainer.innerHTML = '';

  for (const key in params) {
    const label = document.createElement('label');
    label.textContent = key;

    const input = document.createElement('input');
    input.type = 'number';
    input.name = key;
    input.value = params[key];
    input.oninput = () => applyLayout(type);

    layoutSettingsContainer.appendChild(label);
    layoutSettingsContainer.appendChild(input);
  }

  applyLayout(type);
}

function applyLayout(type) {
  const params = {};
  layoutSettingsContainer.querySelectorAll('input').forEach(input => {
    params[input.name] = parseFloat(input.value);
  });

  const layoutMap = {
    GridLayout: go.GridLayout,
    LayeredDigraphLayout: go.LayeredDigraphLayout,
    ForceDirectedLayout: go.ForceDirectedLayout
  };

  diagram.layout = $(layoutMap[type], params);
  diagram.layoutDiagram(true);
}

document.getElementById('layoutType').addEventListener('change', e => {
  renderLayoutForm(e.target.value);
});

function convertAllIconsToBase64(callback) {
  const promises = [];

  diagram.nodes.each(node => {
    const data = node.data;
    if (!data || !data.icon) return;

    const url = data.icon;
    if (url.startsWith("data:image")) return; // уже base64

    const p = fetch(url)
      .then(res => res.blob())
      .then(blob => new Promise(resolve => {
        const reader = new FileReader();
        reader.onloadend = () => {
          const base64 = reader.result;
          data.icon = base64; // Заменяем путь на base64
          resolve();
        };
        reader.readAsDataURL(blob);
      }));

    promises.push(p);
  });

  Promise.all(promises).then(callback);
}

function downloadFullMap() {
  const makeOptions = {
    scale: 2,
    background: "white",
    elementFinished: (goObj, elt) => {
      if (goObj instanceof go.Picture && elt instanceof SVGImageElement) {
        const img = goObj.element; // <img> элемент
        if (!img) return;

        const canvas = document.createElement("canvas");
        canvas.width = img.naturalWidth;
        canvas.height = img.naturalHeight;
        const ctx = canvas.getContext("2d");
        ctx.drawImage(img, 0, 0);

        try {
          const dataURL = canvas.toDataURL();
          elt.setAttribute("href", dataURL);
        } catch (err) {
          console.error("Ошибка при встраивании иконки:", err);
        }
      }
    }
  };

  const svg = diagram.makeSvg(makeOptions);
  const svgData = new XMLSerializer().serializeToString(svg);

  const canvas = document.createElement("canvas");
  const ctx = canvas.getContext("2d");
  const img = new Image();

  const svgBlob = new Blob([svgData], { type: "image/svg+xml;charset=utf-8" });
  const url = URL.createObjectURL(svgBlob);

  img.onload = () => {
    canvas.width = img.width;
    canvas.height = img.height;
    ctx.drawImage(img, 0, 0);

    const { jsPDF } = window.jspdf;
    const pdf = new jsPDF({
      orientation: "landscape",
      unit: "px",
      format: [canvas.width, canvas.height]
    });

    pdf.addImage(canvas, "PNG", 0, 0, canvas.width, canvas.height);
    pdf.save("network_map_full.pdf");

    URL.revokeObjectURL(url);
  };

  img.src = url;
}

renderLayoutForm('LayeredDigraphLayout');
</script>
  <!-- Bootstrap 5 JS (необязательно, но полезно для компонентов) -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
