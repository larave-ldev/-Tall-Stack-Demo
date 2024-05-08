// Function to capture content as an image
function captureContent(callback) {
    // Select the div to copy
    const divToCopy = document.querySelector('.quote-details');

    // Create a canvas element to render the div content
    html2canvas(divToCopy, {
        onrendered: function (canvas) {
            // Convert canvas to image
            const imgData = canvas.toDataURL('image/png');

            // Execute the callback function with the image data
            callback(imgData, canvas);
        }
    });
}

document.getElementById('downloadButton').addEventListener('click', function () {
    // Disable the button temporarily to prevent multiple clicks
    this.disabled = true;

    // Capture the content and handle the image data
    captureContent(function (imgData) {
        // Create a link element to trigger the download
        const link = document.createElement('a');
        link.href = imgData;
        link.download = 'image.png';

        // Append the link to the document body
        document.body.appendChild(link);

        // Trigger the download
        link.click();

        // Remove the link from the document body
        document.body.removeChild(link);

        // Re-enable the button after downloading
        document.getElementById('downloadButton').disabled = false;
    });
});

document.getElementById('copyButton').addEventListener('click', function () {

    // Disable the button temporarily to prevent multiple clicks
    this.disabled = true;

    // Capture the content and handle the image data
    captureContent(function (imgData, canvas) {
        setTimeout(function () {
            // Convert canvas to blob
            canvas.toBlob(function (blob) {
                // Copy blob to clipboard
                navigator.clipboard.write([
                    new ClipboardItem({'image/png': blob})
                ]).then(() => {
                    console.log('Image copied to clipboard');
                }).catch(err => {
                    console.error('Failed to copy image to clipboard:', err);
                });
            });
            // Re-enable the button after copying
            document.getElementById('copyButton').disabled = false;
        }, 100); // Adjust the delay as needed
    });
});
