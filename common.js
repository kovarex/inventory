// Shows or hides the dialog (like the edit form) under the button that opened it.
function toggleDialog(event, id)
{
  let element = document.getElementById(id);
  if (element.style.display == 'none')
  {
    for (let other of document.querySelectorAll('.dialog'))
      other.style.display = 'none';
    element.style.display = 'block';
    let button = event.target;
    let buttonPosition = button.getBoundingClientRect();
    let left = Math.min(buttonPosition.left, document.documentElement.clientWidth - element.offsetWidth - 10);
    element.style.left = (Math.max(left, 0) + window.scrollX) + 'px';
    element.style.top = (buttonPosition.bottom + window.scrollY) + 'px';
  }
  else
    element.style.display = 'none';
}

function showEditDialog(event)
{
  toggleDialog(event, 'edit-dialog');
}

// Expands or collapses all the locations of the tree the button belongs to.
function setTreeExpanded(button, expanded)
{
  for (let details of button.closest('.location-tree').querySelectorAll('details'))
    details.open = expanded;
}

// Hovering an area of a plan shows the items inside it, prepared in .plan-tooltips (see renderPlan in src/plan_helper.php).
function planTooltipInit()
{
  let tooltip = null;
  for (let plan of document.querySelectorAll('.plan'))
  {
    let contents = plan.querySelector('.plan-tooltips');
    if (!contents)
      continue;
    if (!tooltip)
    {
      tooltip = document.createElement('div');
      tooltip.className = 'plan-tooltip';
      tooltip.style.display = 'none';
      document.body.appendChild(tooltip);
    }
    for (let link of plan.querySelectorAll('.plan-link[data-location]'))
    {
      let content = contents.querySelector('.plan-tooltip-content[data-location="' + link.dataset.location + '"]');
      if (!content)
        continue;
      // The tooltip shows the name, so the browser's own tooltip would be a duplicate
      let title = link.querySelector('title');
      if (title)
        title.remove();
      link.addEventListener('mouseenter', function()
      {
        tooltip.innerHTML = content.innerHTML;
        tooltip.style.display = 'block';
      });
      link.addEventListener('mousemove', function(event)
      {
        let x = event.clientX + 16, y = event.clientY + 16;
        if (x + tooltip.offsetWidth > document.documentElement.clientWidth)
          x = Math.max(0, event.clientX - tooltip.offsetWidth - 16);
        if (y + tooltip.offsetHeight > document.documentElement.clientHeight)
          y = Math.max(0, event.clientY - tooltip.offsetHeight - 16);
        tooltip.style.left = x + 'px';
        tooltip.style.top = y + 'px';
      });
      link.addEventListener('mouseleave', function()
      {
        tooltip.style.display = 'none';
      });
    }
  }
}

document.addEventListener('DOMContentLoaded', planTooltipInit);

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
