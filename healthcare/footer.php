<?php
// =====================================================
// footer.php — Shared Footer & Real-time Session Polling
// =====================================================
// Included at the bottom of protected pages.
// Polls check-session-status.php every 20s to detect real-time
// account suspensions without requiring manual page navigation.
// =====================================================
?>
<script>
(function() {
    setInterval(async function() {
        try {
            const res = await fetch('check-session-status.php');
            const data = await res.json();
            if (data && data.status === 'suspended') {
                window.location.href = 'login.php?suspended=1';
            }
        } catch (e) {
            // Fail silently on network hiccups
        }
    }, 20000); // 20-second polling interval
})();
</script>
</body>
</html>
