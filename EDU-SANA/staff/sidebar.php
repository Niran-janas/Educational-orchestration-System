<!-- staff/sidebar.php -->
<div class="col-md-3">
    <div class="card sticky-top" style="top:20px;">
        <div class="card-header bg-success text-white text-center">
            <h5><?php echo $_SESSION['name']; ?></h5>
            <small><?php echo $_SESSION['class']; ?></small>
        </div>
        <div class="list-group list-group-flush">
            <a href="dashboard.php" class="list-group-item list-group-item-action">📊 Dashboard</a>
            <a href="attendance.php" class="list-group-item list-group-item-action active">✅ Mark Attendance</a>
            <a href="../auth/logout.php" class="list-group-item list-group-item-action text-danger">🚪 Logout</a>
        </div>
    </div>
</div>
