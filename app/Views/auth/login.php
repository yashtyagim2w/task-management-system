<main class="d-flex flex-fill align-items-center justify-content-center bg-light">
    <div class="card shadow-sm border-0 w-100 mw-400">
        <div class="card-body p-4">

            <h1 class="h4 fw-bold text-center mb-4">Login</h1>

            <form action="/login" method="post" class="d-flex flex-column gap-3">

                <div>
                    <label for="email">Email</label>
                    <input 
                        type="email" 
                        name="email" 
                        placeholder="example@gmail.com" 
                        minlength="5" 
                        maxlength="128" 
                        class="form-control" 
                        required
                        focused
                    >
                </div>
                
                <div>
                    <label for="password">Password</label>
                    <input 
                        type="password" 
                        name="password" 
                        placeholder="password"
                        minlength="6" 
                        maxlength="32" 
                        class="form-control" 
                        required
                    >
                </div>

                <button type="submit" class="btn btn-primary w-100 fw-500"> Login </button>
            </form>

        </div>
    </div>
</main>

<script>
    const flashMsessage = <?= json_encode($flash_message ?? null) ?>;
    
    // show flash message if exists
    if(flashMsessage && flashMsessage.message) {
        Swal.fire({
            icon: "error",
            title: "Login Failed",
            text: flashMsessage.message,
            confirmButtonText: "Try Again"
        });
    }
</script>