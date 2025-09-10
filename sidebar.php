<aside class="d-flex flex-column flex-shrink-0 p-3 text-white" id="sidebar" style="width: 250px; height: 100vh; position: fixed; left: 0; top: 0; transition: transform 0.3s ease; background-color: #103f97ff;">
    <a href="/" class="d-flex align-items-center mb-3 mb-md-0 me-md-auto text-white text-decoration-none">
        <span class="fs-4" style="color: #FADADD;">SMS</span>
        <button class="btn btn-link text-white ms-auto d-lg-none" id="sidebarToggle" style="color: #FADADD;">
            <i class="bi bi-list fs-4"></i>
        </button>
    </a>
    <hr style="border-color: #B2D8D8;">
    <ul class="nav nav-pills flex-column mb-auto">
        <li class="nav-item">
            <a href="admin_dashboard.php" class="nav-link" aria-current="page" style="color: #FADADD;">
                <i class="bi bi-speedometer2 me-2"></i>
                Dashboard
            </a>
        </li>
        
        <?php if ($_SESSION['role'] == 'admin'): ?>
            <li>
                <a href="manage_students.php" class="nav-link" style="color: #FADADD;">
                    <i class="bi bi-people me-2"></i>
                    Students
                </a>
            </li>
            <li>
                <a href="manage_instructors.php" class="nav-link" style="color: #FADADD;">
                    <i class="bi bi-person-badge me-2"></i>
                    Instructors
                </a>
            </li>
            <li>
                <a href="manage_subjects.php" class="nav-link" style="color: #FADADD;">
                    <i class="bi bi-book me-2"></i>
                    Subjects
                </a>
            </li>
            <li>
                <a href="manage_prerequisites.php" class="nav-link" style="color: #FADADD;">
                    <i class="bi bi-list-check me-2"></i>
                    Pre-requisites
                </a>
            </li>
            <li>
                <a href="enroll_students.php" class="nav-link" style="color: #FADADD;">
                    <i class="bi bi-person-plus me-2"></i>
                    Enroll Students
                </a>
            </li>
            <li>
                <a href="manage_grades.php" class="nav-link" style="color: #FADADD;">
                    <i class="bi bi-check-lg me-2"></i>
                    Manage Grades
                </a>
            </li>
        <?php elseif ($_SESSION['role'] == 'instructor'): ?>
            <li>
                <a href="my_classes.php" class="nav-link" style="color: #FADADD;">
                    <i class="bi bi-book me-2"></i>
                    My Classes
                </a>
            </li>
            <li>
                <a href="manage_grades.php" class="nav-link" style="color: #FADADD;">
                    <i class="bi bi-check-lg me-2"></i>
                    Grade Students
                </a>
            </li>
        <?php elseif ($_SESSION['role'] == 'student'): ?>
            <li>
                <a href="my_courses.php" class="nav-link" style="color: #FADADD;">
                    <i class="bi bi-journals me-2"></i>
                    My Courses
                </a>
            </li>
            <li>
                <a href="view_grades.php" class="nav-link" style="color: #FADADD;">
                    <i class="bi bi-file-earmark-bar-graph me-2"></i>
                    View Grades
                </a>
            </li>
        <?php endif; ?>
    </ul>
    <hr style="border-color: #B2D8D8;">
    <div class="dropdown">
        <a href="#" class="d-flex align-items-center text-white text-decoration-none dropdown-toggle" id="dropdownUser1" data-bs-toggle="dropdown" aria-expanded="false" style="color: #FADADD;">
            <img src="https://github.com/mdo.png" alt="" width="32" height="32" class="rounded-circle me-2">
            <strong style="color: #FADADD;"><?php echo $_SESSION['username']; ?></strong>
        </a>
        <ul class="dropdown-menu dropdown-menu-dark text-small shadow" aria-labelledby="dropdownUser1" style="background-color: #2f508dff;">
            <li><a class="dropdown-item" href="#" style="color: #FADADD;">Settings</a></li>
            <li><a class="dropdown-item" href="#" style="color: #FADADD;">Profile</a></li>
            <li><hr class="dropdown-divider" style="border-color: #B2D8D8;"></li>
            <li><a class="dropdown-item" href="logout.php" style="color: #FADADD;">Sign out</a></li>
        </ul>
    </div>
</aside>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        const sidebar = document.getElementById('sidebar');
        const mainContent = document.getElementById('main-content');
        const sidebarToggle = document.getElementById('sidebarToggle');
        
        if(sidebar && mainContent && sidebarToggle) {
            sidebarToggle.addEventListener('click', function() {
                document.body.classList.toggle('collapsed');
            });
        }
    });
</script>