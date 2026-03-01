<!-- includes/footer.php -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script>
  $(document).ready(function() {
    if ($('.datatable').length) {
      $('.datatable').DataTable({
        language: {
          url: 'https://cdn.datatables.net/plug-ins/1.13.6/i18n/id.json'
        },
        responsive: true
      });
    }
    // Auto hide toasts
    setTimeout(() => {
      document.querySelectorAll('.toast').forEach(t => bootstrap.Toast.getOrCreateInstance(t).hide());
    }, 4000);
  });
</script>
</body>
</html>
