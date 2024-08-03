var Fn = {
    validaRut: function(rutCompleto) {
        if (!/^[0-9]+-[0-9kK]{1}$/.test(rutCompleto)) {
            return false;
        }
        var tmp = rutCompleto.split("-");
        var digv = tmp[1];
        var rut = tmp[0];
        if (digv == "K") digv = "k";
        return (Fn.dv(rut) == digv);
    },
    dv: function(T) {
        var M = 0,
            S = 1;
        for (; T; T = Math.floor(T / 10))
            S = (S + T % 10 * (9 - M++ % 6)) % 11;
        return S ? S - 1 : "k";
    },
    formateaRut: function(rut) {
        var actual = rut.replace(/^0+/, "").replace(/\./g, "").replace(/-/g, "").toUpperCase();
        if (actual.length <= 1) {
            return actual;
        }
        var inicio = actual.slice(0, -1);
        var dv = actual.slice(-1);
        return inicio + "-" + dv;
    }
};

// Formatear y validar RUT al escribir
jQuery(document).on("input", ".rut-input", function() {
    var rut = jQuery(this).val().replace(/[^0-9kK]/g, "").toUpperCase();
    jQuery(this).val(Fn.formateaRut(rut));
});

jQuery(document).on("blur", ".rut-input", function() {
    var rut = jQuery(this).val().replace(/[^0-9kK]/g, "").toUpperCase();
    jQuery(this).val(Fn.formateaRut(rut));
    if (!Fn.validaRut(jQuery(this).val())) {
        jQuery(this).addClass("is-invalid");
    } else {
        jQuery(this).removeClass("is-invalid");
    }
});

// Limitar longitud del RUT
jQuery(".rut-input").attr("maxlength", "10").attr("minlength", "9");
