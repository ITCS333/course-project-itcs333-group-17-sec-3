/*
  Requirement: Populate the "Course Resources" list page.
*/

// --- Element Selections ---
const listSection = document.querySelector('#resource-list-section');

// --- Functions ---

/**
 * Create one <article> for a resource
 */
function createResourceArticle(resource) {
  const { id, title, description } = resource;

  // Create article
  const article = document.createElement('article');

  // Title
  const h2 = document.createElement('h2');
  h2.textContent = title;

  // Description
  const p = document.createElement('p');
  p.textContent = description;

  // View link
  const a = document.createElement('a');
  a.textContent = 'View Resource & Discussion';
  a.href = `details.html?id=${id}`;  // IMPORTANT

  // Append elements
  article.appendChild(h2);
  article.appendChild(p);
  article.appendChild(a);

  return article;
}

/**
 * Load resources from resources.json and render them
 */
async function loadResources() {
  try {
    const res = await fetch('resources.json');
    const data = await res.json();

    // Clear old content
    listSection.innerHTML = '';

    // Loop and add each article
    data.forEach(resource => {
      const article = createResourceArticle(resource);
      listSection.appendChild(article);
    });

  } catch (error) {
    console.error('Error loading resources:', error);
    listSection.textContent = 'Failed to load resources.';
  }
}

// --- Initial Page Load ---
loadResources();
