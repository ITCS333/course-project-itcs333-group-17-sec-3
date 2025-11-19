// --- Global Data Store ---
let currentAssignmentId = null;
let currentComments = [];

// --- Element Selections ---
const assignmentTitle = document.querySelector('#assignment-title');
const assignmentDueDate = document.querySelector('#assignment-due-date');
const assignmentDescription = document.querySelector('#assignment-description');
const assignmentFilesList = document.querySelector('#assignment-files-list');
const commentList = document.querySelector('#comment-list');
const commentForm = document.querySelector('#comment-form');
const newCommentText = document.querySelector('#new-comment-text');

// --- Functions ---

function getAssignmentIdFromURL() {
  const params = new URLSearchParams(window.location.search);
  return params.get('id');
}

function renderAssignmentDetails(assignment) {
  assignmentTitle.textContent = assignment.title;
  assignmentDueDate.textContent = `Due: ${assignment.dueDate}`;
  assignmentDescription.textContent = assignment.description;

  assignmentFilesList.innerHTML = '';
  assignment.files.forEach(file => {
    const li = document.createElement('li');
    const a = document.createElement('a');
    a.href = '#';
    a.textContent = file;
    li.appendChild(a);
    assignmentFilesList.appendChild(li);
  });
}

function createCommentArticle(comment) {
  const article = document.createElement('article');
  article.className = 'border p-2 mb-2';

  const p = document.createElement('p');
  p.textContent = comment.text;

  const footer = document.createElement('footer');
  footer.textContent = `Posted by: ${comment.author}`;

  article.appendChild(p);
  article.appendChild(footer);

  return article;
}

function renderComments() {
  commentList.innerHTML = '';
  currentComments.forEach(comment => {
    const article = createCommentArticle(comment);
    commentList.appendChild(article);
  });
}

function handleAddComment(event) {
  event.preventDefault();
  const text = newCommentText.value.trim();
  if (!text) return;

  const newComment = { author: 'Student', text };
  currentComments.push(newComment);

  renderComments();
  newCommentText.value = '';
}

async function initializePage() {
  currentAssignmentId = getAssignmentIdFromURL();
  if (!currentAssignmentId) {
    assignmentTitle.textContent = 'Error: No assignment ID provided.';
    return;
  }

  try {
    const [assignmentsRes, commentsRes] = await Promise.all([
      fetch('assignments.json'),
      fetch('comments.json')
    ]);

    const assignments = await assignmentsRes.json();
    const commentsData = await commentsRes.json();

    const assignment = assignments.find(a => a.id === currentAssignmentId);
    currentComments = commentsData[currentAssignmentId] || [];

    if (assignment) {
      renderAssignmentDetails(assignment);
      renderComments();
      commentForm.addEventListener('submit', handleAddComment);
    } else {
      assignmentTitle.textContent = 'Error: Assignment not found.';
    }
  } catch (err) {
    assignmentTitle.textContent = 'Error loading assignment data.';
    console.error(err);
  }
}

// --- Initial Page Load ---
initializePage();
