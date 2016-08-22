var externalFile = '';

var lintExternalFile = function(obj, url) {
    externalFile = url;

    $('.report_filename p', '.report').html('FILE: ' + externalFile.substr(externalFile.lastIndexOf('/') + 1));
    $.get('jslint/do_remote_stuff.php?action=read&url=' + url, function( fileSource ) {
        option = { browser: true, devel: true, maxerr: 10000000 };
        $('textarea', '#JSLINT_SOURCE').html(fileSource);
        obj.jslint(fileSource, option);
    });


};



var handleErrorObject = function(errors) {

    $(function(){
        var totalItems = errors.length,
            totalErrors = 0,
            totalWarnings = 0,
            lines = [],
            totalLines = 0,
            i,
            allErrors = [];

        for(i = 0; i < totalItems; i = i + 1){
            if (errors[i] === null) {
                break;
            }

            var out = {};
            out.line = parseInt(errors[i].line, 10);
            out.col = parseInt(errors[i].character, 10);
            out.reason = errors[i].reason;
            out.evidence = errors[i].evidence;
            out.id = errors[i].id;
            out.errorClass = getClassForError(out.reason);
            if (out.errorClass != 'ignore') {
                if (out.errorClass == 'error') {
                    totalErrors++;
                } else {
                    totalWarnings++;
                }
                if (lines.indexOf(out.line) < 0) {
                    totalLines++;
                }
                lines.push(out.line);
                if(out.line < 100) {
                    if(out.line < 10) {
                        out.line = ' ' + out.line;
                    }
                    out.line = ' ' + out.line;
                }
                $('pre', '.report').append('<span class="line-number '+out.errorClass+'" title="' + out.col +'">'+out.line+'</span> ' + out.reason + '\n');
                allErrors.push(out);
            }
        }

        totalItems = totalErrors + totalWarnings;
        if (totalItems > 0) {
            $('.report_summary', '.report').html(' FOUND ' + totalErrors + ' ERROR(S) AND ' + totalWarnings + ' WARNING(S) AFFECTING ' + totalLines + ' LINE(S)');
        } else {
            $('.report_summary', '.report').html(' GREAT JOB. NO ERRORS AND NO WARNINGS FOUND :-) ').addClass('clean');
        }
        $.post('jslint/do_remote_stuff.php?action=session', {data: allErrors}, function() { location.reload(); });
    });
};


var getClassForError = function (reason) {

    if (ignoreError(reason) === true) {
        return 'ignore';
    }

    if (reason.toLowerCase().substr(0, 6) === 'unused') {
        return 'warning';
    }

    if (reason.toLowerCase().substr(0, 11) === 'empty block') {
        return 'warning';
    }

    return 'error';
};



var ignoreError = function (reason) {

    if (reason.toLowerCase().substr(0, 6) === 'unused') {
        return true;
    }

    if (reason.toLowerCase().substr(0, 11) === 'empty block') {
        return true;
    }

    return false;
}

