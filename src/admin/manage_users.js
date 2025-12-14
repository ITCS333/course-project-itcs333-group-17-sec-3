/*
  Requirement: Add interactivity and data management to the Admin Portal.

  Instructions:
  1. Link this file to your HTML using a <script> tag with the 'defer' attribute.
     Example: <script src="manage_users.js" defer></script>
  2. Implement the JavaScript functionality as described in the TODO comments.
  3. All data management will be done by manipulating the 'students' array
     and re-rendering the table.
*/

// --- Global Data Store ---
// This array will be populated with data fetched from 'students.json'.
let students = [];

// --- Element Selections ---
// We can safely select elements here because 'defer' guarantees
// the HTML document is parsed before this script runs.

// TODO: Select the student table body (tbody).
const studentTableBody = document.querySelector("tbody");

// TODO: Select the "Add Student" form.
// (You'll need to add id="add-student-form" to this form in your HTML).
const addStudentForm = document.getElementById("add-student-form");

// TODO: Select the "Change Password" form.
// (You'll need to add id="password-form" to this form in your HTML).
const changePasswordForm = document.getElementById("password-form");

// TODO: Select the search input field.
// (You'll need to add id="search-input" to this input in your HTML).
const searchInput = document.getElementById("search-input");

// TODO: Select all table header (th) elements in thead.
const tableHeaders = document.querySelectorAll("thead th");

// --- Functions ---

/**
 * TODO: Implement the createStudentRow function.
 * This function should take a student object {name, id, email} and return a <tr> element.
 * The <tr> should contain:
 * 1. A <td> for the student's name.
 * 2. A <td> for the student's ID.
 * 3. A <td> for the student's email.
 * 4. A <td> containing two buttons:
 * - An "Edit" button with class "edit-btn" and a data-id attribute set to the student's ID.
 * - A "Delete" button with class "delete-btn" and a data-id attribute set to the student's ID.
 */
function createStudentRow(student) {
  // ... your implementation here ...
  const tr = document.createElement("tr");

  // Name
  const nameTd = document.createElement("td");
  nameTd.textContent = student.name;
  tr.appendChild(nameTd);

  // ID
  const idTd = document.createElement("td");
  idTd.textContent = student.id;
  tr.appendChild(idTd);

  // Email
  const emailTd = document.createElement("td");
  emailTd.textContent = student.email;
  tr.appendChild(emailTd);

    // Actions
  const actionsTd = document.createElement("td");

  const editBtn = document.createElement("button");
  editBtn.textContent = "Edit";
  editBtn.className = "edit-btn btn btn-warning me-2";
  editBtn.dataset.id = student.id; //to store the student ID
  actionsTd.appendChild(editBtn);

    const deleteBtn = document.createElement("button");
  deleteBtn.textContent = "Delete";
  deleteBtn.className = "delete-btn btn btn-danger";
  deleteBtn.dataset.id = student.id; // store the student ID
  actionsTd.appendChild(deleteBtn);

  tr.appendChild(actionsTd);

    return tr;


}

/**
 * TODO: Implement the renderTable function.
 * This function takes an array of student objects.
 * It should:
 * 1. Clear the current content of the `studentTableBody`.
 * 2. Loop through the provided array of students.
 * 3. For each student, call `createStudentRow` and append the returned <tr> to `studentTableBody`.
 */
function renderTable(studentArray) {
  // ... your implementation here ...
  if (!studentTableBody) return;
studentTableBody.innerHTML = ""; // clear old rows

  studentArray.forEach(student => {
    const row = createStudentRow(student);
    studentTableBody.appendChild(row);
  });
}

/**
 * TODO: Implement the handleChangePassword function.
 * This function will be called when the "Update Password" button is clicked.
 * It should:
 * 1. Prevent the form's default submission behavior.
 * 2. Get the values from "current-password", "new-password", and "confirm-password" inputs.
 * 3. Perform validation:
 * - If "new-password" and "confirm-password" do not match, show an alert: "Passwords do not match."
 * - If "new-password" is less than 8 characters, show an alert: "Password must be at least 8 characters."
 * 4. If validation passes, show an alert: "Password updated successfully!"
 * 5. Clear all three password input fields.
 */
// function handleChangePassword(event) {
//   // ... your implementation here ...
//   event.preventDefault();

//   const current = document.getElementById("current-password").value;
//   const newPass = document.getElementById("new-password").value;
//   const confirmPass = document.getElementById("confirm-password").value;

//   if (newPass !== confirmPass) {
//     alert("Passwords do not match.");
//     return;
//   }

//   if (newPass.length < 8) {
//     alert("Password must be at least 8 characters.");
//     return;
//   }

//   alert("Password updated successfully!");

//   document.getElementById("current-password").value = "";
//   document.getElementById("new-password").value = "";
//   document.getElementById("confirm-password").value = "";
// }

function handleChangePassword(event) {
  event.preventDefault();

  // Get input values
  const studentId = document.getElementById("student-id").value.trim();
  const current = document.getElementById("current-password").value.trim();
  const newPass = document.getElementById("new-password").value.trim();
  const confirmPass = document.getElementById("confirm-password").value.trim();

  // Frontend validation
  if (!studentId || !current || !newPass || !confirmPass) {
      alert("Please fill in all fields.");
      return;
  }

  if (newPass.length < 8) {
      alert("Password must be at least 8 characters.");
      return;
  }

  if (newPass !== confirmPass) {
      alert("Passwords do not match.");
      return;
  }

  // Prepare JSON body for backend
  const payload = {
      student_id: studentId,
      current_password: current,
      new_password: newPass
  };

  fetch("api/index.php?action=change_password", {
      method: "POST",
      headers: {
          "Content-Type": "application/json"
      },
      credentials: "include", 
      body: JSON.stringify(payload)
  })
  .then(res => res.json())
  .then(data => {
      if (data.success) {
          alert(data.message);
          // Clear input fields
          document.getElementById("current-password").value = "";
          document.getElementById("new-password").value = "";
          document.getElementById("confirm-password").value = "";
      } else {
          alert(`Error: ${data.message}`);
      }
  })
  .catch(err => {
      console.error("Fetch error:", err);
      alert("An unexpected error occurred.");
  });
}

// Attach to form submit
// document.getElementById("change").addEventListener("click", handleChangePassword);




/**
 * TODO: Implement the handleAddStudent function.
 * This function will be called when the "Add Student" button is clicked.
 * It should:
 * 1. Prevent the form's default submission behavior.
 * 2. Get the values from "student-name", "student-id", and "student-email".
 * 3. Perform validation:
 * - If any of the three fields are empty, show an alert: "Please fill out all required fields."
 * - (Optional) Check if a student with the same ID already exists in the 'students' array.
 * 4. If validation passes:
 * - Create a new student object: { name, id, email }.
 * - Add the new student object to the global 'students' array.
 * - Call `renderTable(students)` to update the view.
 * 5. Clear the "student-name", "student-id", "student-email", and "default-password" input fields.
 */
function handleAddStudent(event) {
  // 1. Prevent the form's default submission behavior
  event.preventDefault();
  // 2. Get the values from "student-name", "student-id", and "student-email".
  // here We no longer use "student-id" input because backend generates it from email prefix
  const name = document.getElementById("student-name").value.trim();
  const email = document.getElementById("student-email").value.trim();
  const password = document.getElementById("default-password").value.trim();
  // 3. Perform validation:
  if (!name || !email || !password) {
    alert("Please fill out all required fields.");
    return;
  }
  // 4. Create a new student object { name, id, email }.
  //   I am not using 'id' because backend derives it from email prefix
  const newStudent = { name, email, password };
  // 5. Send POST request to backend
  fetch("api/index.php", {
    method: "POST",
    credentials: "include",

    headers: { "Content-Type": "application/json" },
    body: JSON.stringify(newStudent)
  })
    .then(res => res.json())
    .then(data => {
      if (data.success) {
        alert("Student added successfully!");
        // 6. Clear the input fields
        document.getElementById("student-name").value = "";
        document.getElementById("student-email").value = "";
        document.getElementById("default-password").value = "";

        // Reload students from backend
        // we fetch fresh data from backend to keep JSON dynamic
        loadStudentsAndInitialize();
      } else {
        alert(data.message || "Failed to add student.");
      }
    })
    .catch(err => console.error("Error adding student:", err));

}



/**
 * TODO: Implement the handleTableClick function.
 * This function will be an event listener on the `studentTableBody` (event delegation).
 * It should:
 * 1. Check if the clicked element (`event.target`) has the class "delete-btn".
 * 2. If it is a "delete-btn":
 * - Get the `data-id` attribute from the button.
 * - Update the global 'students' array by filtering out the student with the matching ID.
 * - Call `renderTable(students)` to update the view.
 * 3. (Optional) Check for "edit-btn" and implement edit logic.
 */
// function handleTableClick(event) {
//   // ... your implementation here ...
//   if (event.target.classList.contains("delete-btn")) {
//     const id = event.target.dataset.id;
//     students = students.filter(s => s.id !== id);
//     renderTable(students);
//   }
// }

function handleTableClick(event) {
  const target = event.target;
  if (event.target.classList.contains("delete-btn")) {

    const id = event.target.dataset.id;  // this is the email prefix
    if (!id) return;

    //  1. Call backend delete API
    fetch(`api/index.php?student_id=${id}`, {
      method: "DELETE",
      credentials: "include"
    })
    .then(res => res.json())
    .then(data => {

      if (data.success) {
        //  2. Remove from frontend
        students = students.filter(s => s.id !== id);
        renderTable(students);
      } else {
        alert(data.message || "Failed to delete student");
      }

    })
    .catch(err => console.error("Delete error:", err));
  }
      // EDIT
      if (target.classList.contains("edit-btn")) {
        const id = target.dataset.id;
        const student = students.find(s => s.id === id);
        if (!student) return;

        // Pre-fill modal fields
        document.getElementById("edit-student-id").value = student.id;
        document.getElementById("edit-name").value = student.name;
        document.getElementById("edit-email").value = student.email;

        // Show modal
        const modalEl = document.getElementById("editStudentModal");
        if (window.bootstrap && modalEl) {
  const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
  modal.show();
}

      }
    }
// const editStudentForm = document.getElementById("edit-student-form");

// editStudentForm.addEventListener("submit", function(event) {
//     event.preventDefault(); // stop the form from reloading the page

//     const studentId = document.getElementById("edit-student-id").value;
//     const newName = document.getElementById("edit-name").value.trim();
//     const newEmail = document.getElementById("edit-email").value.trim();

//     if (!newName || !newEmail) {
//         alert("Please fill out all fields.");
//         return;
//     }
const editStudentForm = document.getElementById("edit-student-form");

if (editStudentForm) {
    editStudentForm.addEventListener("submit", function(event) {
        event.preventDefault(); // stop the form from reloading the page

        const studentId = document.getElementById("edit-student-id").value;
        const newName = document.getElementById("edit-name").value.trim();
        const newEmail = document.getElementById("edit-email").value.trim();

        if (!newName || !newEmail) {
            alert("Please fill out all fields.");
            return;
        }
      

    const updatedData = {
        student_id: studentId,
        name: newName,
        email: newEmail
    };

    fetch("api/index.php", {
        method: "PUT",
      credentials: "include",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(updatedData)
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            alert("Student updated successfully!");

            // Update the frontend array
            const studentIndex = students.findIndex(s => s.id === studentId);
            if (studentIndex !== -1) {
                students[studentIndex].name = newName;
                students[studentIndex].email = newEmail;
                students[studentIndex].id = newEmail.split("@")[0];
            }

            renderTable(students);

            // Close the modal
            const modalEl = document.getElementById("editStudentModal");
            const modal = bootstrap.Modal.getInstance(modalEl);
            modal.hide();
        } else {
            alert(data.message || "Failed to update student");
        }
    })
    .catch(err => console.error("Update error:", err));
});
}

function handleSearch(event) {
  if (!searchInput) return;
  const term = searchInput.value.toLowerCase();

  if (term === "") {
    renderTable(students);
    return;
  }

  const filtered = students.filter(s =>
    s.name.toLowerCase().includes(term)
  );

  renderTable(filtered);
}


/**
 * TODO: Implement the handleSort function.
 * This function will be called when any `th` in the `thead` is clicked.
 * It should:
 * 1. Identify which column was clicked (e.g., `event.currentTarget.cellIndex`).
 * 2. Determine the property to sort by ('name', 'id', 'email') based on the index.
 * 3. Determine the sort direction. Use a data-attribute (e.g., `data-sort-dir="asc"`) on the `th`
 * to track the current direction. Toggle between "asc" and "desc".
 * 4. Sort the global 'students' array *in place* using `array.sort()`.
 * - For 'name' and 'email', use `localeCompare` for string comparison.
 * - For 'id', compare the values as numbers.
 * 5. Respect the sort direction (ascending or descending).
 * 6. After sorting, call `renderTable(students)` to update the view.
 */
function handleSort(event) {
  // ... your implementation here ...

  const th = event.currentTarget;
  const index = th.cellIndex;

  let prop = "";
  if (index === 0) prop = "name";
  if (index === 1) prop = "id";
  if (index === 2) prop = "email";

  let direction = th.dataset.sortDir || "asc";
  direction = direction === "asc" ? "desc" : "asc";
  th.dataset.sortDir = direction;

  students.sort((a, b) => {
    let comp;

    if (prop === "id") {
      comp = Number(a.id) - Number(b.id);
    } else {
      comp = a[prop].localeCompare(b[prop]);
    }

    return direction === "asc" ? comp : -comp;
  });

  renderTable(students);
}




/**
 * TODO: Implement the loadStudentsAndInitialize function.
 * This function needs to be 'async'.
 * It should:
 * 1. Use the `fetch()` API to get data from 'students.json'.
 * 2. Check if the response is 'ok'. If not, log an error.
 * 3. Parse the JSON response (e.g., `await response.json()`).
 * 4. Assign the resulting array to the global 'students' variable.
 * 5. Call `renderTable(students)` to populate the table for the first time.
 * 6. After data is loaded, set up all the event listeners:
 * - "submit" on `changePasswordForm` -> `handleChangePassword`
 * - "submit" on `addStudentForm` -> `handleAddStudent`
 * - "click" on `studentTableBody` -> `handleTableClick`
 * - "input" on `searchInput` -> `handleSearch`
 * - "click" on each header in `tableHeaders` -> `handleSort`
 */
async function loadStudentsAndInitialize() {

  try {
    // Fetch students from backend (GET request)
    const response = await fetch("api/index.php"); // GET request
    if (!response.ok) {
      console.error("Failed to fetch students from backend.");
      return;
    }
    // 2- Parse the JSON response
    const data = await response.json();

    // Check if backend returned success
    if (!data.success) {
      console.error("Backend error:", data.message);
      return;
    }

    // 3- Map backend data to frontend format
    // Backend returns: { name, email, ... }
    // Frontend expects: { name, id, email }
    students = data.data.map(user => {
      const studentId = user.email.split("@")[0]; // get student ID from email prefix
      return {
        name: user.name,
        id: studentId,
        email: user.email
      };
    });

    // 4- Render the table
    renderTable(students);
      if (changePasswordForm) {
    changePasswordForm.addEventListener("submit", handleChangePassword);
  }

  if (addStudentForm) {
    addStudentForm.addEventListener("submit", handleAddStudent);
  }

  if (studentTableBody) {
    studentTableBody.addEventListener("click", handleTableClick);
  }

  if (searchInput) {
   searchInput.addEventListener("input", handleSearch);
  }

  tableHeaders.forEach(th => {
 if (th) th.addEventListener("click", handleSort);  });

  // if (editStudentForm) {
  //   editStudentForm.addEventListener("submit", () => {});
  // }

  } catch (error) {
    console.error("Error loading students:", error);
  }
}

// --- Initial Page Load ---
// Call the main async function to start the application.

loadStudentsAndInitialize();
