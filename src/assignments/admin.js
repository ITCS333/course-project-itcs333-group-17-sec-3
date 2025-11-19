let assignments = [];

const assignmentForm = document.querySelector('#assignment-form');
const assignmentsTableBody = document.querySelector('#assignments-tbody');

function createAssignmentRow(assignment) {
  const tr = document.createElement('tr');

  const titleTd = document.createElement('td');
  titleTd.textContent = assignment.title;

  const dueDateTd = document.createElement('td');
  dueDateTd.textContent = assignment.dueDate;

  const actionsTd = document.createElement('td');

  const editBtn = document.createElement('button');
  editBtn.textContent = 'Edit';
  editBtn.className = 'btn btn-warning btn-sm edit-btn me-2';
  editBtn.setAttribute('data-id', assignment.id);

  const deleteBtn = document.createElement('button');
  deleteBtn.textContent = 'Delete';
  deleteBtn.className = 'btn btn-danger btn-sm delete-btn';
  deleteBtn.setAttribute('data-id', assignment.id);

  actionsTd.appendChild(editBtn);
  actionsTd.appendChild(deleteBtn);

  tr.appendChild(titleTd);
  tr.appendChild(dueDateTd);
  tr.appendChild(actionsTd);

  return tr;
}

function renderTable() {
  assignmentsTableBody.innerHTML = '';
  assignments.forEach(assignment => {
    const row = createAssignmentRow(assignment);
    assignmentsTableBody.appendChild(row);
  });
}

function handleAddAssignment(event) {
  event.preventDefault();

  const title = document.querySelector('#assignment-title').value.trim();
  const description = document.querySelector('#assignment-description').value.trim();
  const dueDate = document.querySelector('#assignment-due-date').value;
  const fileInput = document.querySelector('#assignment-files');
  const files = Array.from(fileInput.files).map(file => file.name);

  if (!title || !dueDate) return;

  const newAssignment = {
    id: `asg_${Date.now()}`,
    title,
    description,
    dueDate,
    files
  };

  assignments.push(newAssignment);
  renderTable();
  assignmentForm.reset();
}

function handleTableClick(event) {
  const target = event.target;

  if (event.target.classList.contains('delete-btn')) {
    const idToDelete = target.getAttribute('data-id');
    assignments = assignments.filter(asg => asg.id !== idToDelete);
    renderTable();
  }

  if (event.target.classList.contains('edit-btn')) {
    const idToEdit = target.getAttribute('data-id');
    const assignment = assignments.find(asg => asg.id === idToEdit);
    if (assignment) {
      document.querySelector('#assignment-title').value = assignment.title;
      document.querySelector('#assignment-description').value = assignment.description;
      document.querySelector('#assignment-due-date').value = assignment.dueDate;

      // File input can't be pre-filled for security reasons
      // So we just remove the old assignment and let user re-attach files
      assignments = assignments.filter(asg => asg.id !== idToEdit);
    }
  }
}

async function loadAndInitialize() {
  try {
    const response = await fetch('assignments.json');
    if (!response.ok) throw new Error('Failed to load assignments.json');
    assignments = await response.json();
  } catch (error) {
    console.warn('Using empty assignment list due to error:', error);
    assignments = [];
  }

  renderTable();
  assignmentForm.addEventListener('submit', handleAddAssignment);
  assignmentsTableBody.addEventListener('click', handleTableClick);
}

loadAndInitialize();
