<div class="auth-container">
    <div class="auth-box">
        <h2>Login to <?php echo APP_NAME; ?></h2>
        
        <form method="POST" action="/login" class="auth-form">
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
            
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" required>
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>
            
            <button type="submit" class="btn btn-primary">Login</button>
        </form>
        
        <p class="auth-footer">
            Don't have an account? <a href="/register">Create one</a>
        </p>
    </div>
</div>
