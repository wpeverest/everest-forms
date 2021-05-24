/* eslint-disable max-len */
/* global everest_forms_params */
jQuery( function ( $ ) {
	'use strict';
    var evf_calender = {
        init: function() {
            $( '.date-dropdown-field').each( function() {
               
                evf_calender.create_select($(this));
            });

            $('body').on('change', '[id*=evf-calander-select]', function() {
                evf_calender.refresh_select( $( this ).parent().find('input' ) , $( this ).attr( 'id' ) );
            });

        },

        create_select: function (el) {
            //el.hide();
            var inputData  	 = $( el ).data();
            var maxYear = new Date().getFullYear(), minYear = maxYear-100;
            var maxMonth, minMonth, maxDay, minDay;
            var minMinute, minHour, maxMinute, maxHour;
            var id = el.attr('id');
            el.parent().append('<div class="evf-calendar-dropdown-wrap"> <div class="evf-calendar-dropdown-list-wrap"></div> </div>');
            if( inputData.dateTime == 'date' || inputData.dateTime == 'date-time' ) {
                //Load Default
                var years = '<ul id="evf-calander-select-years-' + id + '">' + evf_calender.years( 'li', minYear, maxYear) + '</ul>';
                el.parent().find('.evf-calendar-dropdown-list-wrap').append(years);
                
                var months = '<ul id="evf-calander-select-months-' + id + '">' + evf_calender.months( 'li', minMonth, minYear, maxMonth, maxYear, el) + '</ul>';
                el.parent().find('.evf-calendar-dropdown-list-wrap').append(months);
                
                var days = '<ul id="evf-calander-select-days-' + id + '">' + evf_calender.days( 'li', minDay, minMonth, minYear, maxDay, maxMonth, maxYear, el) + '</ul>';
                el.parent().find('.evf-calendar-dropdown-list-wrap').append(days);
            }

            if( inputData.dateTime == 'time' || inputData.dateTime == 'date-time' ) {
                //Load Default
                var timeFormat = inputData.timeFormat;
                var hours = '<ul id="evf-calander-select-hours-' + id + '">' + evf_calender.hours( 'li', minHour, maxHour, timeFormat) + '</ul>';
                el.parent().find('.evf-calendar-dropdown-list-wrap').append(hours);
                var minutes = '<ul id="evf-calander-select-minutes-' + id + '">' + evf_calender.minutes( 'li', minMinute, maxMinute, minHour, maxHour, el) + '</ul>';
                el.parent().find('.evf-calendar-dropdown-list-wrap').append(minutes);
            }
            el.parent().find('.evf-calendar-dropdown-wrap').append('<button class='evf-btn-calendar'>ok</button>');
            evf_calender.refresh_select(el, null);
            //Chack Options

              
        },

        refresh_select: function(el, select) {
            var inputData  	 = $( el ).data();
            var maxYear = new Date().getFullYear(), minYear = maxYear-100;
            var maxMonth, minMonth, maxDay, minDay;
            var minMinute, minHour, maxMinute, maxHour;
            var timeFormat = inputData.timeFormat;
            var disablePastDate = el.attr('disable_past_date');
            var selectedYear, selectedMonth, selectedDay, selectedHour, selectedMinute, selectedDate;

            if( typeof( inputData.maxDate)  != 'undefined' ) {
                maxYear = inputData.maxDate.substr(0,4);
                maxMonth = inputData.maxDate.substr(5,2);
                maxDay = inputData.maxDate.substr(8,2);
            }
            
            if(disablePastDate == true) {
                minYear = new Date().getFullYear();
                minMonth = new Date().getMonth();
                minDay = new Date().getDate();
            }

            if( typeof( inputData.minDate ) != 'undefined' ) {
                minYear = inputData.minDate.substr(0,4);
                minMonth = inputData.minDate.substr(5,2);
                minDay = inputData.minDate.substr(8,2);
            }

            if( inputData.maxMinute != '' ) {
                maxMinute = inputData.maxMinute;
                maxHour = inputData.maxHour;
            }

            if( inputData.minMinute != '' ) {
                minMinute = inputData.minMinute;
                minHour = inputData.minHour;
            }

            if( inputData.dateTime == 'date' || inputData.dateTime == 'date-time' ) {

                if( select == null ) {

                    var dateDefault = inputData.dateDefault;

                    el.parent().find( '[id*=evf-calander-select-years]' ).html( evf_calender.years( 'li', minYear, maxYear ) );

                    el.parent().find( '[id*=evf-calander-select-months]' ).html( evf_calender.months( 'li', minMonth, minYear, maxMonth, maxYear, el ) );
        
                    el.parent().find('[id*=evf-calander-select-days]').html( evf_calender.days( 'li', minDay, minMonth, minYear, maxDay, maxMonth, maxYear, el ) );    
                
                    if( dateDefault == true ) {
                        el.parent().find( '[id*=evf-calander-select-years]' ).val( new Date().getFullYear() )
                        el.parent().find( '[id*=evf-calander-select-months]' ).val( new Date().getMonth() )
                        el.parent().find( '[id*=evf-calander-select-days]' ).val( new Date().getDate() )
                    }

                } else if (select.match(/select-years/)) {
                    el.parent().find( '[id*=evf-calander-select-months]' ).html( evf_calender.months( 'li', minMonth, minYear, maxMonth, maxYear, el ) );
        
                    el.parent().find('[id*=evf-calander-select-days]').html( evf_calender.days( 'li', minDay, minMonth, minYear, maxDay, maxMonth, maxYear, el ) );    
                } else if (select.match(/select-months/)) {   
                    el.parent().find('[id*=evf-calander-select-days]').html( evf_calender.days( 'li', minDay, minMonth, minYear, maxDay, maxMonth, maxYear, el ) );    
                }

                selectedYear = el.parent().find( '[id*=evf-calander-select-years]' ).val();
                selectedMonth = el.parent().find( '[id*=evf-calander-select-months]' ).val();
                selectedDay = el.parent().find( '[id*=evf-calander-select-days]' ).val();

                selectedDate = selectedYear + '-' + selectedMonth + '-' + selectedDay;
            }

            if( inputData.dateTime == 'time' || inputData.dateTime == 'date-time' ) {
                
                if(select == null) {
                    el.parent().find('[id*=evf-calander-select-hours]').html( evf_calender.hours( 'li', minHour, maxHour, timeFormat ) );
                    el.parent().find('[id*=evf-calander-select-minutes]').html( evf_calender.minutes( 'li', minMinute, maxMinute, minHour, maxHour, el ) );
                } else if (select.match(/select-hours/)) {
                    el.parent().find('[id*=evf-calander-select-minutes]').html( evf_calender.minutes( 'li', minMinute, maxMinute, minHour, maxHour, el ) );
                }
                selectedHour = el.parent().find( '[id*=evf-calander-select-hours]' ).val();
                selectedMinute = el.parent().find( '[id*=evf-calander-select-minutes]' ).val();
                selectedDate += ' '+selectedHour + ':'+selectedMinute;
            }       
            el.val(selectedDate );
        },
        days: function ( tag, startD, startM, startY, endD, endM, endY, el ) {
            var days = "";
            var i;
            for (i = 1; i < 32;  i++ ) {
                if( el.parent().find('[id*=evf-calander-select-years]').val() == startY && el.parent().find('[id*=evf-calander-select-months]').val() == ( startM - 1 ) && i < startD ) {
                    continue;
                }
                if( el.parent().find('[id*=evf-calander-select-years]').val() == endY && el.parent().find('[id*=evf-calander-select-months]').val() == ( endM - 1 ) && i > endD ) {
                    break;
                }
                if(i < 10 ) {
                    i = '0' + i;
                }
                days += "<" + tag + " value = " + i + ">" + i +"</" + tag +">";
            }	
            return days;
        },

        months: function ( tag, startM, startY, endM, endY, el ) {
            var i, l;
            var months = "";
            var list_months = [
                    'January',
                    'Febuary',
                    'March',
                    'April',
                    'May',
                    'June',
                    'July',
                    'August',
                    'September',
                    'October',
                    'November',
                    'December'
                ];
            for (i = 0, l = list_months.length; i < l; i++) {
                if( el.parent().find('[id*=evf-calander-select-years]').val() == startY ) {
                    if( i < (startM - 1) ) {
                        // console.log(list_months[i])
                        continue;
                    }
                }
                if( el.parent().find('[id*=evf-calander-select-years]').val() == endY ) {
                    if( i == endM ) {
                        break;
                    }
                }
                // console.log(list_months[i])

                months += "<" + tag + " value = " + i + ">" + list_months[i] + "</" + tag +">";
            }	
            return months;
        },
        
        years: function( tag, startY, endY)
        {
            var years = "";
            var i;
            for (i = endY; i > startY - 1;  i-- ) {
                if(i < 10 ) {
                    i = '0' + i;
                }
                years += "<" + tag + " value = " + i + ">" + i +"</" + tag +">";
            }		
            return years;
        },
        
        hours: function( tag = '', start = 0, end = 23, format = 'g:i A' ) {
            var hours = '', i, hour, period;
            if(format == 'g:i A') {
                for( i=start; i <= end; i++ ) {
                    if( i < 12 ) {
                        if( i == 0 ) {
                            hour = 12;
                        } else {
                            hour = i;
                        }
                        period = 'AM';
                    } else {
                        if( i-12 == 0 ) {
                            hour = 12;
                        } else {
                            hour = i-12;
                        }
                        period = 'PM';
                    }

                    hours += "<" + tag + " value = " + i + ">" + hour + ' ' + period +"</" + tag +">";
                }
            } else {
                for (i = start; i <= end;  i++ ) {
                    if( i < 12 ) {
                        if( i == 0 ) {
                            hour = 12;
                        }
                        hour = i;
                        period = 'AM';
                    } else {
                        hour = i;
                        period = 'PM';
                    }
                    
                    hours += "<" + tag + " value = " + i + ">" + hour + ' ' + period +"</" + tag +">";
                }	
            }
            return hours;
        },

        minutes: function( tag = '', start = 0, end = 59, startHour = 0, endHour = 23, el) {
            var i, minutes;
            for( i=0; i <= 59; i++ ) {
                
                if(el.parent().find('[id*=evf-calander-select-hours]').val() == startHour && i < start ) {
                    continue;
                } 
                if(el.parent().find('[id*=evf-calander-select-hours]').val() == endHour && i > end ) {
                    break;
                }

                if(i < 10 ) {
                    i = '0' + i;
                }
                minutes += "<" + tag + " value = " + i + ">" + i +"</" + tag +">";
            }
            return minutes;
        }
    };

    evf_calender.init();
});