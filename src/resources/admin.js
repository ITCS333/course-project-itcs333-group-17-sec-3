/*
  Admin page logic: load resources, add, delete (in-memory only)
*/

// Global data
let resources = [];

// Elements
const resourceForm = document.querySelector('#resource-form');
const resourcesTableBody = document.querySelector('#resources-tbody');

// Create one table row
function createResourceRow(resource) {
  const { id, title, description } = resource;

  const tr = document.createElement('tr');

  const titleTd = document.createElement('td');
  titleTd.textContent = title;

  const descTd = document.createElement('td');
  descTd.textContent = description;

  const actionsTd = document.createElement('td');

  const editBtn = document.createElement('button');
  editBtn.type = 'button';
  editBtn.textContent = 'Edit';
  editBtn.classList.add('edit-btn');
  editBtn.dataset.id = id;

  const deleteBtn = document.createElement('button');
  deleteBtn.type = 'button';
  deleteBtn.textContent = 'Delete';
  deleteBtn.classList.add('delete-btn');
  deleteBtn.dataset.id = id;

  actionsTd.appendChild(editBtn);
  actionsTd.appendChild(deleteBtn);

  tr.appendChild(titleTd);
  tr.appendChild(descTd);
  tr.appendChild(actionsTd);

  return tr;
}

// Render all rows
function renderTable() {
  resourcesTableBody.innerHTML = '';

  resources.forEach(resource => {
    const row = createResourceRow(resource);
    resourcesTableBody.appendChild(row);
  });

  if (resources.length === 0) {
    const emptyRow = document.createElement('tr');
    const emptyCell = document.createElement('td');
    emptyCell.colSpan = 3;
    emptyCell.textContent = 'No resources available.';
    emptyRow.appendChild(emptyCell);
    resourcesTableBody.appendChild(emptyRow);
  }
}

// Handle add resource from form
function handleAddResource(event) {
  event.preventDefault();

  const titleInput = document.querySelector('#resource-title');
  const descInput = document.querySelector('#resource-description');
  const linkInput = document.querySelector('#resource-link');

  const title = titleInput.value.trim();
  const description = descInput.value.trim();
  const link = linkInput.value.trim();

  if (!title || !description || !link) {
    alert('Please fill in all fields before adding a resource.');
    return;
  }

  const newResource = {
    id: `res_${Date.now()}`,
    title,
    description,
    link
  };

  resources.push(newResource);
  renderTable();
  resourceForm.reset();
}

// Handle delete (and optional edit) using event delegation
function handleTableClick(event) {
  const target = event.target;

  // Delete
  if (target.classList.contains('delete-btn')) {
    const idToDelete = target.dataset.id;
    resources = resources.filter(r => r.id !== idToDelete);
    renderTable();
  }

}

// Load initial data from api/resources.json and attach listeners
async function loadAndInitialize() {
  try {
    const response = await fetch('api/resources.json');
    if (response.ok) {
      const data = await response.json();
      resources = Array.isArray(data) ? data : [];
    } else {
      resources = [];
      console.warn('Could not load api/resources.json');
    }
  } catch (err) {
    console.error('Error loading resources:', err);
    resources = [];
  }

  renderTable();

  if (resourceForm) {
    resourceForm.addEventListener('submit', handleAddResource);
  }
  if (resourcesTableBody) {
    resourcesTableBody.addEventListener('click', handleTableClick);
  }
}

// Initial load
loadAndInitialize();
