function showEditDialog(event)
{
  let element = document.getElementById('edit-dialog');
  if (element.style.display == 'none')
  {
    element.style.display = 'block';
    let button = event.target;
    let buttonPosition = button.getBoundingClientRect();
    element.style.left = buttonPosition.x + 'px';
    element.style.top = (buttonPosition.y + button.clientHeight) + 'px';
  }
  else
    element.style.display = 'none';
}

// Floor plan area editor, active on the plan rendered with data-editor-for (see renderPlan in src/plan_helper.php).
// The area is kept in the hidden input #plan-editor-area as "x,y x,y ..." relative to the plan size (0..1).
let planEditor = null;

function planEditorInit()
{
  let svg = document.querySelector('svg[data-editor-for]');
  if (!svg)
    return;
  let input = document.getElementById('plan-editor-area');
  let polygon = document.createElementNS('http://www.w3.org/2000/svg', 'polygon');
  polygon.setAttribute('class', 'plan-area plan-editor-area');
  svg.appendChild(polygon);
  let points = [];
  if (input.value.trim() != '')
    points = input.value.trim().split(/\s+/).map(pair => pair.split(',').map(Number));
  planEditor = {svg: svg, input: input, polygon: polygon, points: points, width: Number(svg.dataset.planWidth), height: Number(svg.dataset.planHeight)};
  svg.addEventListener('click', function(event)
  {
    let matrix = svg.getScreenCTM().inverse();
    let point = new DOMPoint(event.clientX, event.clientY).matrixTransform(matrix);
    let x = Math.min(Math.max(point.x / planEditor.width, 0), 1);
    let y = Math.min(Math.max(point.y / planEditor.height, 0), 1);
    planEditor.points.push([x, y]);
    planEditorUpdate();
  });
  planEditorUpdate();
}

function planEditorUpdate()
{
  planEditor.polygon.setAttribute('points', planEditor.points.map(p => (p[0] * planEditor.width) + ',' + (p[1] * planEditor.height)).join(' '));
  planEditor.input.value = planEditor.points.map(p => p[0].toFixed(4) + ',' + p[1].toFixed(4)).join(' ');
}

function planEditorUndo()
{
  planEditor.points.pop();
  planEditorUpdate();
}

function planEditorClear()
{
  planEditor.points = [];
  planEditorUpdate();
}

document.addEventListener('DOMContentLoaded', planEditorInit);
