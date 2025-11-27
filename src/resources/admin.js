/*
  Requirement: Make the "Manage Resources" page interactive.

  Instructions:
  1. Link this file to `admin.html` using:
     <script src="admin.js" defer></script>
  
  2. In `admin.html`, add an `id="resources-tbody"` to the <tbody> element
     inside your `resources-table`.
  
  3. Implement the TODOs below.
*/

// --- Global Data Store ---
// This will hold the resources loaded from the JSON file.
let resources = [];

// --- Element Selections ---
// Select the resource form ('#resource-form').
const resourceForm = document.querySelector('#resource-form');

// Select the resources table body ('#resources-tbody').
const resourcesTableBody = document.querySelector('#resources-tbody');

// --- Functions ---

/**
 * Create a <tr> element for a single resource.
 * resource: { id, title, description, link? }
 */
function createResourceRow(resource) {
  const { id, title, description } = resource;

  const tr = document.createElement('tr');

  // Title cell
  const titleTd = document.createElement('td');
  titleTd.textContent = title;

  // Description cell
  const descTd = document.createElement('td');
  descTd.textContent = description;

  // Actions cell
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

/**
 * Render the entire table from the global `resources` array.
 */
function renderTable() {
  // Clear existing rows
  resourcesTableBody.innerHTML = '';

  // Loop through resources and append rows
  resources.forEach(resource => {
    const row = createResourceRow(resource);
    resourcesTableBody.appendChild(row);
  });

  // Optional: لو حابة تحطين رسالة لما تكون القائمة فاضية
  if (resources.length === 0) {
    const emptyRow = document.createElement('tr');
    const emptyCell = document.createElement('td');
    emptyCell.colSpan = 3;
    emptyCell.textContent = 'No resources available.';
    resourcesTableBody.appendChild(emptyRow);
    emptyRow.appendChild(emptyCell);
  }
}

/**
 * Handle form submission: add a new resource (in-memory only).
 */
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

  // Add to global array
  resources.push(newResource);

  // Re-render table
  renderTable();

  // Reset form
  resourceForm.reset();
}

/**
 * Handle clicks on the table body (event delegation).
 * For now, we implement only Delete as required.
 */
function handleTableClick(event) {
  const target = event.target;

  // Delete button
  if (target.classList.contains('delete-btn')) {
    const idToDelete = target.dataset.id;

    // Filter out the deleted resource
    resources = resources.filter(resource => resource.id !== idToDelete);

    // Re-render table
    renderTable();
  }

  // (Optional) Edit button – مو مطلوب صراحة في الـ TODO، بس ممكن تضيفينه لاحقًا
  // if (target.classList.contains('edit-btn')) { ... }
}

/**
 * Load initial data from resources.json and wire up event listeners.
 */
async function loadAndInitialize() {
  try {
    const response = await fetch('resources.json');
    if (!response.ok) {
      console.warn('Could not load resources.json, using empty list instead.');
      resources = [];
    } else {
      const data = await response.json();
      // نتوقع أن البيانات تكون مصفوفة من الكائنات { id, title, description, link }
      resources = Array.isArray(data) ? data : [];
    }
  } catch (error) {
    console.error('Error loading resources.json:', error);
    resources = [];
  }

  // أول رندر للجدول
  renderTable();

  // Event listeners
  if (resourceForm) {
    resourceForm.addEventListener('submit', handleAddResource);
  }

  if (resourcesTableBody) {
    resourcesTableBody.addEventListener('click', handleTableClick);
  }
}

// --- Initial Page Load ---
loadAndInitialize();
