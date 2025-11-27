/*
  Requirement: Populate the resource detail page and discussion forum.
*/

// --- Global Data Store ---
let currentResourceId = null;
let currentComments = [];

// --- Element Selections ---
const resourceTitle = document.querySelector('#resource-title');
const resourceDescription = document.querySelector('#resource-description');
const resourceLink = document.querySelector('#resource-link');
const commentList = document.querySelector('#comment-list');
const commentForm = document.querySelector('#comment-form');
const newComment = document.querySelector('#new-comment');

// --- Functions ---

/**
 * Get the resource id from the URL query string.
 * Example: details.html?id=res_1
 */
function getResourceIdFromURL() {
  const params = new URLSearchParams(window.location.search);
  const id = params.get('id');
  return id;
}

/**
 * Render the main resource info on the page.
 */
function renderResourceDetails(resource) {
  resourceTitle.textContent = resource.title;
  resourceDescription.textContent = resource.description;
  resourceLink.href = resource.link;
}

/**
 * Create a single comment <article>.
 * comment: { author, text }
 */
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

/**
 * Render all comments from currentComments.
 */
function renderComments() {
  // امسحي التعليقات القديمة
  commentList.innerHTML = '';

  if (!currentComments || currentComments.length === 0) {
    const emptyMsg = document.createElement('p');
    emptyMsg.textContent = 'No comments yet. Be the first to comment.';
    commentList.appendChild(emptyMsg);
    return;
  }

  currentComments.forEach(c => {
    const article = createCommentArticle(c);
    commentList.appendChild(article);
  });
}

/**
 * Handle adding a new comment from the form.
 */
function handleAddComment(event) {
  event.preventDefault();

  const commentText = newComment.value.trim();
  if (!commentText) {
    return;
  }

  const newObj = {
    author: 'Student',
    text: commentText
  };

  // أضفناه للمصفوفة في الذاكرة فقط
  currentComments.push(newObj);

  // أعد رسم التعليقات
  renderComments();

  // نظّف التكسيت إريا
  newComment.value = '';
}

/**
 * Initialize page: load resource + comments and wire events.
 */
async function initializePage() {
  currentResourceId = getResourceIdFromURL();

  if (!currentResourceId) {
    resourceTitle.textContent = 'Resource not found.';
    return;
  }

  try {
    const [resourcesRes, commentsRes] = await Promise.all([
      fetch('resources.json'),
      fetch('resource-comments.json')
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
    console.error('Error loading resource details:', error);
    resourceTitle.textContent = 'Error loading resource.';
  }
}

// --- Initial Page Load ---
initializePage();
