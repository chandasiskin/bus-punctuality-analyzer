Dropzone.options.uploadForm = {
    autoProcessQueue: true,
    uploadMultiple: false,

    init: function() {

        this.on("sending", function(file, xhr) {
            xhr.responseType = "blob";
        });

        this.on("success", function(file) {

            const xhr = file.xhr;

            // Get filename
            let disposition = xhr.getResponseHeader('Content-Disposition');
            let filename = "download.csv";

            if (disposition && disposition.includes("filename=")) {
                filename = disposition
                    .split("filename=")[1]
                    .replace(/"/g, '');
            }

            const blob = xhr.response;

            const url = window.URL.createObjectURL(blob);

            const a = document.createElement("a");
            a.href = url;
            a.download = filename;

            document.body.appendChild(a);
            a.click();

            window.URL.revokeObjectURL(url);
            a.remove();

            this.removeAllFiles(true);
        });

        this.on("error", function(file, errorMessage) {
            console.error(errorMessage);
        });
    }
};