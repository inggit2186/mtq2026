<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Generating PDF...</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <style>
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            background: #f0f9ff;
        }
        .container {
            text-align: center;
            padding: 40px;
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }
        .spinner {
            width: 50px;
            height: 50px;
            border: 4px solid #e0f2fe;
            border-top: 4px solid #0891b2;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto 20px;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        h2 {
            color: #0891b2;
            margin: 0 0 10px 0;
        }
        p {
            color: #64748b;
            margin: 0;
        }
        .error {
            color: #dc2626;
            background: #fef2f2;
            padding: 10px 20px;
            border-radius: 8px;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="container" id="pdf-container">
        <div class="spinner"></div>
        <h2>Membuat PDF...</h2>
        <p>Silakan tunggu sebentar</p>
    </div>

    <div id="pdf-content" style="display:none;">
        {!! $htmlContent !!}
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', async function() {
            const element = document.getElementById('pdf-content');
            const container = document.getElementById('pdf-container');

            try {
                // Configure html2pdf options
                const options = {
                    margin: 8,
                    filename: '{{ $filename }}',
                    image: { type: 'jpeg', quality: 0.98 },
                    html2canvas: {
                        scale: 2,
                        useCORS: true,
                        logging: false,
                        letterRendering: true
                    },
                    jsPDF: {
                        unit: 'mm',
                        format: 'a4',
                        orientation: 'landscape'
                    },
                    pagebreak: { mode: ['avoid-all', 'css', 'legacy'] }
                };

                // Generate and download PDF
                await html2pdf().set(options).from(element).save();

                // Show success and close after delay
                container.innerHTML = '<div class="spinner"></div><h2 style="color:#16a34a;">PDF Berhasil!</h2><p>File akan otomatis terdownload.</p><p style="margin-top:10px;font-size:12px;color:#94a3b8;">Jika tidak terdownload, <a href="javascript:window.close()">tutup halaman ini</a></p>';

            } catch (error) {
                container.innerHTML = '<div class="error"><strong>Gagal membuat PDF</strong><br>' + error.message + '</div>';
            }
        });
    </script>
</body>
</html>