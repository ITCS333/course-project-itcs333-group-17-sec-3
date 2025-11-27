/*
  List page logic: show all resources to students.
*/

// Section that يحتوي على الـ articles
const listSection = document.querySelector('#resource-list-section');

// Create one <article> for a resource
function createResourceArticle(resource) {
  const { id, title, description } = resource;

  const article = document.createElement('article');

  const h2 = document.createElement('h2');
  h2.textContent = title;

  const p = document.createElement('p');
  p.textContent = description;

  const a = document.createElement('a');
  a.textContent = 'View Resource & Discussion';
  a.href = `details.html?id=${id}`;

  article.appendChild(h2);
  article.appendChild(p);
  article.appendChild(a);

  return article;
}

// Load from api/resources.json and render
async function loadResources() {
  try {
    const res = await fetch('api/resources.json');
    const data = await res.json();

    listSection.innerHTML = '';

    data.forEach(resource => {
      const article = createResourceArticle(resource);
      listSection.appendChild(article);
    });
  } catch (error) {
    console.error('Error loading resources:', error);
    listSection.textContent = 'Failed to load resources.';
  }
}

// Initial load
loadResources();
