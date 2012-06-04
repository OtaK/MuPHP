(function($)
{

    var curSelected,
        methods =
        {
            generateHtml: function(targetAction)
            {
                if (targetAction == curSelected) return false;
                curSelected = targetAction;

                $target.slideUp();
                var htmlRes = '<fieldset><legend>Informations de l\'action</legend>', input;
                for (var field in possibleActions[targetAction].fields)
                {
                    if (!possibleActions[targetAction].fields.hasOwnProperty(field)) continue;
                    input = '';
                    switch (possibleActions[targetAction].fields[field].type)
                    {
                        case 'text':
                        case 'url':
                            input = '<label>' + possibleActions[targetAction].fields[field].label + '<input type="' + possibleActions[targetAction].fields[field].type + '" class="input-text" name="' + field + '" placeholder="' + possibleActions[targetAction].fields[field].placeholder + '" /></label>';
                            break;
                        case 'checkbox':
                            input = '<label for="' + field + '">\n<input type="checkbox" name="' + field + '" id="' + field + '" style="display: none;" />\n<span class="custom checkbox"></span> ' + possibleActions[targetAction].fields[field].label + '\n</label>\n';
                            break;
                        case 'select':
                            var
                                i = 0,
                                firstLabel,
                                list = '<ul>';
                            input = '<label for="' + field + '">' + possibleActions[targetAction].fields[field].placeholder + '</label>\n<select id="' + field + '" name="' + field + '" style="display:none;">\n';
                            for (var val in possibleActions[targetAction].fields[field].possibleValues)
                            {
                                if (!possibleActions[targetAction].fields[field].possibleValues.hasOwnProperty(val)) continue;
                                if (!i) firstLabel = possibleActions[targetAction].fields[field].possibleValues[val];
                                input += '<option value="' + possibleActions[targetAction].fields[field].possibleValues[val] + '">' + possibleActions[targetAction].fields[field].possibleValues[val] + '</option>\n';
                                list += '<li>' + possibleActions[targetAction].fields[field].possibleValues[val] + '</li>';
                                ++i;
                            }
                            list += '</ul><br />';
                            input += '</select>\n<div class="custom dropdown">\n<a href="#" class="current">' + firstLabel + '</a>\n<a href="#" class="selector"></a>\n' + list;
                            break;
                    }
                    htmlRes += input + '\n';
                }
                $target.html(htmlRes + '</fieldset>').slideDown();

                return false;
            }
        };

})(jQuery);

var formGenerator = function(possibleActions)
{
    var curSelected;
    function displaySpecialFields(targetAction, $target)
    {
    	if (targetAction == curSelected) return false;
    	curSelected = targetAction;

    	$target.slideUp();
    	var htmlRes = '<fieldset><legend>Informations de l\'action</legend>', input;
    	for (var field in possibleActions[targetAction].fields)
    	{
            if (!possibleActions[targetAction].fields.hasOwnProperty(field)) continue;
    		input = '';
    		switch (possibleActions[targetAction].fields[field].type)
    		{
    			case 'text':
                case 'url':
    				input = '<label>' + possibleActions[targetAction].fields[field].label + '<input type="' + possibleActions[targetAction].fields[field].type + '" class="input-text" name="' + field + '" placeholder="' + possibleActions[targetAction].fields[field].placeholder + '" /></label>';
    				break;
    			case 'checkbox':
    				input = '<label for="' + field + '">\n<input type="checkbox" name="' + field + '" id="' + field + '" style="display: none;" />\n<span class="custom checkbox"></span> ' + possibleActions[targetAction].fields[field].label + '\n</label>\n';
    				break;
    			case 'select':
    				var
    					i = 0,
    					firstLabel,
    					list = '<ul>';
    				input = '<label for="' + field + '">' + possibleActions[targetAction].fields[field].placeholder + '</label>\n<select id="' + field + '" name="' + field + '" style="display:none;">\n';
    				for (var val in possibleActions[targetAction].fields[field].possibleValues)
    				{
                        if (!possibleActions[targetAction].fields[field].possibleValues.hasOwnProperty(val)) continue;
    					if (!i) firstLabel = possibleActions[targetAction].fields[field].possibleValues[val];
    					input += '<option value="' + possibleActions[targetAction].fields[field].possibleValues[val] + '">' + possibleActions[targetAction].fields[field].possibleValues[val] + '</option>\n';
    					list += '<li>' + possibleActions[targetAction].fields[field].possibleValues[val] + '</li>';
    					++i;
    				}
    				list += '</ul><br />';
    				input += '</select>\n<div class="custom dropdown">\n<a href="#" class="current">' + firstLabel + '</a>\n<a href="#" class="selector"></a>\n' + list;
    				break;
    		}
    		htmlRes += input + '\n';
    	}
    	$target.html(htmlRes + '</fieldset>').slideDown();

    	return false;
    }
};
