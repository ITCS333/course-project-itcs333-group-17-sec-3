/*
  Requirement: Populate the "Weekly Course Breakdown" list page.

  Instructions:
  1. Link this file to `list.html` using:
     <script src="list.js" defer></script>

  2. In `list.html`, add an `id="week-list-section"` to the
     <section> element that will contain the weekly articles.

  3. Implement the TODOs below.
*/

// --- Element Selections ---
// TODO: Select the section for the week list ('#week-list-section').
const listSection = document.querySelector('#week-list-section');

// --- Functions ---

/**
 * TODO: Implement the createWeekArticle function.
 * It takes one week object {id, title, startDate, description}.
 * It should return an <article> element matching the structure in `list.html`.
 * - The "View Details & Discussion" link's `href` MUST be set to `details.html?id=${id}`.
 * (This is how the detail page will know which week to load).
 */
function createWeekArticle(week) {
  // ... your implementation here ...
  const { id, title, startDate, description } = week;

  const article = document.createElement('article');

  const h2 = document.createElement('h2');
  h2.textContent = title;

  const pDate = document.createElement('p');
  pDate.textContent = `Starts on: ${startDate}`;

  const pDesc = document.createElement('p');
  pDesc.textContent = description;

  const link = document.createElement('a');
  link.href = `details.html?id=${encodeURIComponent(id)}`;
  link.textContent = 'View Details & Discussion';

  article.appendChild(h2);
  article.appendChild(pDate);
  article.appendChild(pDesc);
  article.appendChild(link);

  return article;
}

/**
 * TODO: Implement the loadWeeks function.
 * This function needs to be 'async'.
 * It should:
 * 1. Use `fetch()` to get data from 'weeks.json'.
 * 2. Parse the JSON response into an array.
 * 3. Clear any existing content from `listSection`.
 * 4. Loop through the weeks array. For each week:
 * - Call `createWeekArticle()`.
 * - Append the returned <article> element to `listSection`.
 */
async function loadWeeks() {
  // ... your implementation here ...
  try {
    const response = await fetch('api/weeks.json');
    if (!response.ok) {
      throw new Error('Failed to load weeks.json');
    }

    const data = await response.json();

    listSection.innerHTML = ''; // Clear old content if any

    data.forEach(week => {
      const article = createWeekArticle(week);
      listSection.appendChild(article);
    });

  } catch (error) {
    console.error('Error loading weeks:', error);
    listSection.textContent = 'Failed to load weekly breakdown.';
  }
}

// --- Initial Page Load ---
// Call the function to populate the page.
loadWeeks();
