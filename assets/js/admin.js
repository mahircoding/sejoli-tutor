console.log("admin.js");
var enableInstructorsField = document.querySelector('input[name="tutor_option[enable_course_marketplace]"]');
if (enableInstructorsField !== null) {
    // enableInstructorsField.checked = false;
    // enableInstructorsField.disabled = true;
    if (enableInstructorsField.checked) {
        enableInstructorsField.parentNode.parentNode.innerHTML += '<div class="sejoli-tutor-admin-group warning">Sejoli tidak support marketplace Course untuk instruktor.</div>';
    } else {
        enableInstructorsField.parentNode.parentNode.innerHTML += '<div class="sejoli-tutor-admin-group info">Sejoli tidak support marketplace Course untuk instruktor.</div>';
    }
}

jQuery(document).ready(function() {

    jQuery('#sejoli-tutor-serial-btn').on('click', function(ev) {
        console.log("Sejoli tutor dashboard");
        ev.preventDefault();
        ev.target.disabled = true;
        jQuery('#serial-wrap').addClass('loading');
        window.sejoli_tutor_admin.credential.serial_number = jQuery('#sejoli_tutor_serial').val();

        var credential = window.sejoli_tutor_admin.credential;

        var formData = new FormData();
        formData.append("application", 'tutor-lms');
        formData.append("domain", credential.domain);
        formData.append("email", credential.email);
        formData.append("path", credential.path);
        formData.append("serial_number", credential.serial_number);

        fetch(window.sejoli_tutor_admin.cek_serial_ajax, {
                method: "POST",
                cache: "no-cache",
                // credentials: "same-origin",
                body: formData,
            })
            .then(function(res) {
                return res.json();
            })
            .then(function(res) {
                console.log(res);
                if (res.code == 200) {
                    console.log("Simpan data berhasil");
                    var nonce = jQuery('#sejoli_tutor_nonce').val();
                    fetch(window.sejoli_tutor_admin.save_serial_ajax, {
                            method: "POST",
                            cache: "no-cache",
                            // credentials: "same-origin",
                            body: JSON.stringify({ action: 'sejoli_tutor_save_credential', nonce: nonce, data: res.data }),
                        })
                        .then(function(res1) {
                            return res1.json();
                        })
                        .then(function(res1) {
                            console.log(res1);
                            if (res1.code == 200) {
                                jQuery('#serial-wrap').addClass('success');
                                jQuery('#serial-wrap-info').html('<br><br>Validasi berhasil');
                                window.location.reload()
                            }
                        });


                } else {
                    console.log("Simpan data GAGAL");
                    jQuery('#serial-wrap').addClass('danger');
                    jQuery('#serial-wrap-info').html('<br><br>Kode yang Anda masukkan salah atau domain belum diaktifkan. <br><br>Akses kode lisensi: <br><br><a class="button" href="https://sejolitutor.brizpress.com/member-area/aktivasi-lisensi/" target="_blank">sejolitutor.brizpress.com</a>');
                }

                setTimeout(function() {
                    ev.target.disabled = false;
                    jQuery('#serial-wrap').removeClass('success').removeClass('danger').removeClass('loading');
                }, 1000);

            });


    });

    jQuery('#sejoli-tutor-save-setting-btn').on('click', function(ev) {
        ev.preventDefault();
        ev.target.disabled = true;
        ev.target.innerHTML = 'Loading';

        var redirect_tutor_dashboard = document.getElementById('redirect_tutor_dashboard');

        if (redirect_tutor_dashboard !== null) {

            var nonce = jQuery('#sejoli_tutor_nonce').val();
            var ajax_data = {
                redirect_tutor_dashboard: redirect_tutor_dashboard.checked,
            };

            fetch(window.sejoli_tutor_admin.save_setting_ajax, {
                    method: "POST",
                    cache: "no-cache",
                    // credentials: "same-origin",
                    body: JSON.stringify({ action: 'sejoli_tutor_save_credential', nonce: nonce, data: ajax_data }),
                })
                .then(function(res1) {
                    return res1.json();
                })
                .then(function(res1) {
                    ev.target.innerHTML = 'Simpan Pengaturan';

                    setTimeout(function() {
                        ev.target.disabled = false;
                    }, 1000);

                    if (res1.code == 200) {
                        window.location.reload()
                    }
                });
        }

    });
});