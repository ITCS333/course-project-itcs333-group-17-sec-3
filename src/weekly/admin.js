/*
  Requirement: Make the "Manage Weekly Breakdown" page interactive.

  Instructions:
  1. Link this file to `admin.html` using:
     <script src="admin.js" defer></script>
  
  2. In `admin.html`, add an `id="weeks-tbody"` to the <tbody> element
     inside your `weeks-table`.
  
  3. Implement the TODOs below.
*/

// --- Global Data Store ---
// This will hold the weekly data loaded from the JSON file.
let weeks = [];

// --- Element Selections ---
// TODO: Select the week form ('#week-form').
const weekForm = document.querySelector('#week-form');

// TODO: Select the weeks table body ('#weeks-tbody').
const weeksTableBody = document.querySelector('#weeks-tbody');

// --- Functions ---

/**
 * TODO: Implement the createWeekRow function.
 * It takes one week object {id, title, description}.
 * It should return a <tr> element with the following <td>s:
 * 1. A <td> for the `title`.
 * 2. A <td> for the `description`.
 * 3. A <td> containing two buttons:
 * - An "Edit" button with class "edit-btn" and `data-id="${id}"`.
 * - A "Delete" button with class "delete-btn" and `data-id="${id}"`.
 */
function createWeekRow(week) {
  // ... your implementation here ...
  const { id, title, description } = week;

  const tr = document.createElement('tr');

  const titleTd = document.createElement('td');
  titleTd.textContent = title;

  const descTd = document.createElement('td');
  descTd.textContent = description || '';

  const actionsTd = document.createElement('td');

  const editBtn = document.createElement('button');
  editBtn.textContent = 'Edit';
  editBtn.classList.add('edit-btn');
  editBtn.dataset.id = id;

  const deleteBtn = document.createElement('button');
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
 * TODO: Implement the renderTable function.
 * It should:
 * 1. Clear the `weeksTableBody`.
 * 2. Loop through the global `weeks` array.
 * 3. For each week, call `createWeekRow()`, and
 * append the resulting <tr> to `weeksTableBody`.
 */
function renderTable() {
  // ... your implementation here ...
  if (!weeksTableBody) return;

  weeksTableBody.innerHTML = '';

  weeks.forEach(week => {
    const row = createWeekRow(week);
    weeksTableBody.appendChild(row);
  });
}

/**
 * TODO: Implement the handleAddWeek function.
 * This is the event handler for the form's 'submit' event.
 * It should:
 * 1. Prevent the form's default submission.
 * 2. Get the values from the title, start date, and description inputs.
 * 3. Get the value from the 'week-links' textarea. Split this value
 * by newlines (`\n`) to create an array of link strings.
 * 4. Create a new week object with a unique ID (e.g., `id: \`week_${Date.now()}\``).
 * 5. Add this new week object to the global `weeks` array (in-memory only).
 * 6. Call `renderTable()` to refresh the list.
 * 7. Reset the form.
 */
function handleAddWeek(event) {
  // ... your implementation here ...
  event.preventDefault();

  const titleInput = document.querySelector('#week-title');
  const startDateInput = document.querySelector('#week-start-date');
  const descriptionInput = document.querySelector('#week-description');
  const linksInput = document.querySelector('#week-links');

  const title = titleInput ? titleInput.value.trim() : '';
  const startDate = startDateInput ? startDateInput.value : '';
  const description = descriptionInput ? descriptionInput.value.trim() : '';
  const linksRaw = linksInput ? linksInput.value : '';

  if (!title || !startDate) {
    alert('Please fill in the week title and starting date.');
    return;
  }

  const links = linksRaw
    .split('\n')
    .map(link => link.trim())
    .filter(link => link.length > 0);

  const newWeek = {
    id: `week_${Date.now()}`,
    title,
    startDate,
    description,
    links
  };

  weeks.push(newWeek);
  renderTable();
  if (weekForm) {
    weekForm.reset();
  }
}

/**
 * TODO: Implement the handleTableClick function.
 * This is an event listener on the `weeksTableBody` (for delegation).
 * It should:
 * 1. Check if the clicked element (`event.target`) has the class "delete-btn".
 * 2. If it does, get the `data-id` attribute from the button.
 * 3. Update the global `weeks` array by filtering out the week
 * with the matching ID (in-memory only).
 * 4. Call `renderTable()` to refresh the list.
 */
function handleTableClick(event) {
  // ... your implementation here ...
  const target = event.target;

  if (!target.classList.contains('delete-btn')) {
    return;
  }

  const idToDelete = target.dataset.id;
  weeks = weeks.filter(week => week.id !== idToDelete);
  renderTable();
}

/**
 * TODO: Implement the loadAndInitialize function.
 * This function needs to be 'async'.
 * It should:
 * 1. Use `fetch()` to get data from 'weeks.json'.
 * 2. Parse the JSON response and store the result in the global `weeks` array.
 * 3. Call `renderTable()` to populate the table for the first time.
 * 4. Add the 'submit' event listener to `weekForm` (calls `handleAddWeek`).
 * 5. Add the 'click' event listener to `weeksTableBody` (calls `handleTableClick`).
 */
async function loadAndInitialize() {
  // ... your implementation here ...
  try {
    const response = await fetch('weeks.json');
    if (response.ok) {
      const data = await response.json();
      weeks = Array.isArray(data) ? data : [];
    } else {
      weeks = [];
    }
  } catch (error) {
    console.error('Error loading weeks.json:', error);
    weeks = [];
  }

  renderTable();

  if (weekForm) {
    weekForm.addEventListener('submit', handleAddWeek);
  }

  if (weeksTableBody) {
    weeksTableBody.addEventListener('click', handleTableClick);
  }
}

// --- Initial Page Load ---
// Call the main async function to start the application.
loadAndInitialize();
