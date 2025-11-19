// --- Element Selections ---

const listSection = document.querySelector('#assignment-list-section');

// --- Functions ---

function createAssignmentArticle(assignment) {
  const article = document.createElement('article');

  const title = document.createElement('h2');
  title.textContent = assignment.title;

  const dueDate = document.createElement('p');
  dueDate.textContent = `Due: ${assignment.dueDate}`;

  const description = document.createElement('p');
  description.textContent = assignment.description;

  const link = document.createElement('a');
  link.href = `details.html?id=${assignment.id}`;
  link.textContent = 'View Details & Discussion';

  article.appendChild(title);
  article.appendChild(dueDate);
  article.appendChild(description);
  article.appendChild(link);

  return article;
}

async function loadAssignments() {
  try {
    const response = await fetch('assignments.json');
    if (!response.ok) throw new Error('Failed to load assignments.json');
    const assignments = await response.json();

    listSection.innerHTML = '';
    assignments.forEach(assignment => {
      const article = createAssignmentArticle(assignment);
      listSection.appendChild(article);
    });
  } catch (error) {
    console.error('Error loading assignments:', error);
    listSection.innerHTML = '<p>Unable to load assignments at this time.</p>';
  }
}

// --- Initial Page Load ---
loadAssignments();
