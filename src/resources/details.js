/*
  Details page logic: show one resource + comments.
*/

// Global
let currentResourceId = null;
let currentComments = [];

// Elements
const resourceTitle = document.querySelector('#resource-title');
const resourceDescription = document.querySelector('#resource-description');
const resourceLink = document.querySelector('#resource-link');
const commentList = document.querySelector('#comment-list');
const commentForm = document.querySelector('#comment-form');
const newComment = document.querySelector('#new-comment');

// Get ?id=... from URL
function getResourceIdFromURL() {
  const params = new URLSearchParams(window.location.search);
  return params.get('id');
}

// Fill resource info
function renderResourceDetails(resource) {
  resourceTitle.textContent = resource.title;
  resourceDescription.textContent = resource.description;
  resourceLink.href = resource.link;
}

// Create one comment article
function createCommentArticle(comment) {
  const article = document.createElement('article');
  article.classList.add('comment');

  const p = document.createElement('p');
  p.textContent = comment.text;

  const footer = document.createElement('footer');
  footer.textContent = `Posted by: ${comment.author}`;

  article.appendChild(p);
  article.appendChild(footer);

  return article;
}

// Render all comments
function renderComments() {
  commentList.innerHTML = '';

  if (!currentComments || currentComments.length === 0) {
    const msg = document.createElement('p');
    msg.textContent = 'No comments yet. Be the first to comment.';
    commentList.appendChild(msg);
    return;
  }

  currentComments.forEach(c => {
    const article = createCommentArticle(c);
    commentList.appendChild(article);
  });
}

// Handle new comment
function handleAddComment(event) {
  event.preventDefault();

  const commentText = newComment.value.trim();
  if (!commentText) return;

  const newObj = {
    author: 'Student',
    text: commentText
  };

  currentComments.push(newObj);
  renderComments();
  newComment.value = '';
}

// Initialize page
async function initializePage() {
  currentResourceId = getResourceIdFromURL();

  if (!currentResourceId) {
    resourceTitle.textContent = 'Resource not found.';
    return;
  }

  try {
    const [resourcesRes, commentsRes] = await Promise.all([
      fetch('api/resources.json'),
      fetch('api/comments.json')
    ]);

    const resourcesData = await resourcesRes.json();
    const commentsData = await commentsRes.json();

    const resource = Array.isArray(resourcesData)
      ? resourcesData.find(r => r.id === currentResourceId)
      : null;

    currentComments = commentsData[currentResourceId] || [];

    if (!resource) {
      resourceTitle.textContent = 'Resource not found.';
      return;
    }

    renderResourceDetails(resource);
    renderComments();

    if (commentForm) {
      commentForm.addEventListener('submit', handleAddComment);
    }

  } catch (error) {
    console.error('Error loading details:', error);
    resourceTitle.textContent = 'Error loading resource.';
  }
}

// Initial load
initializePage();
