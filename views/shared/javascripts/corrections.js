(function ($) {
    
    var correctUrl = "http://shapeofthenews.org/admin/newspapers/corrections/correct";
    
    $(document).ready(function() {
        $('.corrections-button').on('click', function(e) {
            //e.stopPropagation();
            var target = $(this).parent();
            postCorrection(target);
        });
        
        
        $('.columns-correction').on('keypress', function(e) {
            if (e.which == 13) {
                var target = $(this).parents('.newspapers-columns');
                postCorrection(target);
            }
        });

        function postCorrection(target) {
            var correctedColumns = target.find('input.columns-correction').val();
            var frontPageId = target.find('input.frontpage-id').val();
            var newspaperId = target.find('input.newspaper-id').val();
            var originalColumns = target.find('input.original-columns').val();
            if (correctedColumns == '') {
                return;
            }
            var data = {'frontPageId': frontPageId,
                        'newspaperId': newspaperId,
                        'correctedColumns' : correctedColumns,
                        'originalColumns' : originalColumns
            };
            $.post(correctUrl, data, successMessage);
        }
        
        function successMessage(data, status, jqXHR) {
            alert('Thanks for the correction!');
        }
    });
})(jQuery);
