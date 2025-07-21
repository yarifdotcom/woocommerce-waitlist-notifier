<?php
$product_id = get_the_ID();
?>
<?php if (!is_user_logged_in()): ?>
    <a href="<?php echo esc_url(wc_get_page_permalink('myaccount')); ?>" class="button alt"
        style="text-decoration: none !important;margin-bottom: 15px;"
    >
        Login to Join Enquiry
    </a>
<?php else: ?>

    <form id="wwn-waitlist-form">
        <input type="hidden" name="product_id" value="<?= esc_attr($product_id); ?>">
        <button type="submit" class="button alt">Join Enquiry</button>
    </form>
<?php endif; ?>

<style>
    #wwn-waitlist-form {
        margin-bottom: 15px;
    }
</style>

<script>
document.getElementById('wwn-waitlist-form').addEventListener('submit', function(e) {
    e.preventDefault();
    const form = e.target;
    const formData = new FormData(form);
    const submitButton = form.querySelector('button[type="submit"]');

    submitButton.disabled = true;
    submitButton.textContent = 'Processing...';

    fetch("<?= admin_url('admin-ajax.php'); ?>", {
        method: "POST",
        body: new URLSearchParams({
            action: "wwn_join_waitlist",
            product_id: formData.get("product_id"),
            email: formData.get("email")
        })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            window.location.href = data.data.redirect_url;
            form.reset();
        }
        else {
            alert(data.data?.message || 'Something went wrong.');
        }
    })
    .catch(() => alert('Failed to submit.'))
    .finally(() => {
        submitButton.disabled = false;
        submitButton.textContent = originalText;
    });
});
</script>
