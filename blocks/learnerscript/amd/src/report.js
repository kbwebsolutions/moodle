/**
 * Standard Report wrapper for Moodle. It calls the central JS file for Report plugin,
 * Also it includes JS libraries like Select2,Datatables and Highcharts
 * @module     block_learnerscript/report
 * @class      report
 * @package    block_learnerscript
 * @copyright  2017 Naveen kumar <naveen@eabyas.in>
 * @since      3.3
 */
define(['block_learnerscript/select2',
    'block_learnerscript/responsive.bootstrap',
    'block_learnerscript/reportwidget',
    'block_learnerscript/chart',
    'block_learnerscript/smartfilter',
    'block_learnerscript/helper',
    'block_learnerscript/ajaxforms',
    'jquery',
    'block_learnerscript/radioslider',
    'block_learnerscript/flatpickr',
    'jqueryui'
], function(select2, DataTable, reportwidget, chart, smartfilter, helper, AjaxForms,
    $, RadiosToSlider, flatpickr) {
    var report;
    var BasicCoursecategories = $('.basicparamsform #id_filter_coursecategories');
    var BasicparamCourse = $('.basicparamsform #id_filter_courses');
    var BasicparamUser = $('.basicparamsform #id_filter_users');
    var BasicparamActivity = $('.basicparamsform #id_filter_activity');

    var FilterCoursecategories = $('.filterform #id_filter_coursecategories');
    var FilterCourse = $('.filterform #id_filter_courses');
    var FilterUser = $('.filterform #id_filter_users');
    var FilterActivity = $('.filterform #id_filter_activity');
    var FilterModule = $('.filterform #id_filter_modules');

    var NumberOfBasicParams = 0;

    return report = {
        init: function(args) {
            /**
             * Initialization
             */
            $.ui.dialog.prototype._focusTabbable = $.noop;
            $.fn.dataTable.ext.errMode = 'none';

            /**
             * Select2 initialization
             */
            $("select[data-select2='1']").select2({
                theme: "classic"
            }).on("select2:selecting", function(e) {
                if ($(this).val() && $(this).data('maximumSelectionLength') &&
                    $(this).val().length >= $(this).data('maximumSelectionLength')) {
                    e.preventDefault();
                    $(this).select2('close');
                }
            });

            /*
             * Report search
             */
            $("#reportsearch").val(args.reportid).trigger('change.select2');
            $("#reportsearch").change(function() {
                var reportid = $(this).find(":selected").val();
                window.location = M.cfg.wwwroot + '/blocks/learnerscript/viewreport.php?id=' + reportid;
            });
            /**
             * Duration buttons
             */
            RadiosToSlider.init($('#segmented-button'), {
                size: 'medium',
                animation: true,
                reportdashboard: false
            });
            /**
             * Duration Filter
             */
            flatpickr('#customrange', {
                mode: 'range',
                onOpen: function(selectedDates, dateStr,instance){
                    instance.clear();
                },
                onClose: function(selectedDates, dateStr, instance) {
                    $('#ls_fstartdate').val(selectedDates[0].getTime() / 1000);
                    $('#ls_fenddate').val((selectedDates[1].getTime() / 1000) + (60 * 60 * 24));
                    require(['block_learnerscript/report'], function(report) {
                        report.CreateReportPage({ reportid: args.reportid, instanceid: args.reportid, reportdashboard: false });
                    });
                }
            });

            /*
             * Get Activities and Enrolled users for selected course
             */
            if (typeof BasicparamCourse != 'undefined' || typeof FilterCourse != 'undefined') {
                $('#id_filter_courses').change(function() {
                    args.courseid = $(this).find(":selected").val();
                    args.categoryid = $('#id_filter_coursecategories').find(":selected").val(); 
                    /*if (BasicparamCourse.length > 0) {
                       smartfilter.CourseData(args);
                    } */
                    if ((BasicparamCourse.length > 0) && (FilterUser.length > 0 || BasicparamUser.length > 0))  {
                        smartfilter.EnrolledUsers({ categoryid: args.categoryid, courseid: args.courseid });
                    }

                    if ((BasicparamCourse.length > 0 || FilterCourse.length > 0 ) && (FilterActivity.length > 0 || BasicparamActivity.length > 0)) {
                        smartfilter.CourseActivities({ categoryid: args.categoryid, courseid: args.courseid  });
                    }
                });
            }

            /*
             * Get Enrolled courses for selected user
             */
            $('#id_filter_users').change(function() {
                var userid = $(this).find(":selected").val();
                var categoryid = $('#id_filter_coursecategories').find(":selected").val();
                var courseid = $('#id_filter_courses').find(":selected").val();
                if (BasicparamUser.length > 0 && FilterCourse.length > 0) {
                    //smartfilter.categoryCourses({categoryid: categoryid, reporttype: args.reporttype, firstelementactive: FirstElementActive });
                    smartfilter.UserCourses({ categoryid: categoryid, userid: userid, reporttype: args.reporttype, firstelementactive: FirstElementActive });
                }          
            });

            $('#id_filter_coursecategories').change(function() {
                var categoryid = $(this).find(":selected").val();
                var userid = $('#id_filter_users').find(":selected").val();
                 if (FilterCourse.length > 0 && args.basicparams.length == 1) {
                     smartfilter.categoryCourses({categoryid: categoryid, reporttype: args.reporttype, firstelementactive: FirstElementActive });
                }
                if (FilterUser.length > 0 && args.basicparams.length == 1) {
                    smartfilter.CategoryUsers({categoryid: categoryid, reporttype: args.reporttype, firstelementactive: FirstElementActive });
                }
                if (FilterUser.length > 0 && args.basicparams.length == 2 && args.basicparams[1].name == 'courses') {
                    //return false;
                } else {
                    if (categoryid > 0 && (BasicparamUser.length > 0) && args.basicparams[1].name == 'users') {
                        if(BasicparamUser.length > 0){
                            FirstElementActive = true;
                        }
                        smartfilter.CategoryUsers({categoryid: categoryid, reporttype: args.reporttype, firstelementactive: FirstElementActive });
                    }
                }
                if (FilterCourse.length > 0 && args.basicparams.length == 2 && args.basicparams[1].name == 'users') {
                    // smartfilter.UserCourses({ categoryid: categoryid, userid: userid, reporttype: args.reporttype, firstelementactive: FirstElementActive });
                } else {
                    if (categoryid > 0 && (BasicparamCourse.length > 0) && args.basicparams[1].name == 'courses') {
                        if(BasicparamCourse.length > 0){
                            FirstElementActive = true;
                        }
                        var courseid = args.filterrequests.filter_courses;
                        smartfilter.categoryCourses({categoryid: categoryid, reporttype: args.reporttype, firstelementactive: FirstElementActive });
                    }
                }      
            });

            schedule.SelectRoleUsers();

            if (args.basicparams != null) {
                 var FirstElementActive = false;
                if (args.basicparams[0].name == 'coursecategories') {
                    //$("#id_filter_courses").trigger('change');
                    $("#id_filter_coursecategories").trigger('change');
                    //$("#id_filter_users").trigger('change');
                    if (BasicparamActivity.length > 0) {
                       NumberOfBasicParams++; 
                    }
                    
                }
            }
           /* if (args.basicparams != null) {
                var FirstElementActive = false;
                if (args.basicparams[0].name == 'users') {
                    NumberOfBasicParams++;
                    if (BasicparamCourse.length > 0) {
                        FirstElementActive = true;
                    }
                    var userid = $("#id_filter_users").find(":selected").val();
                    var categoryid = $('#id_filter_coursecategories').find(":selected").val();
                    if (userid > 0 && categoryid > 0) {
                        //smartfilter.UserCourses({ categoryid: categoryid, userid: userid, reporttype: args.reporttype,
                                                 // firstelementactive: FirstElementActive, triggercourseactivities: true });
                        smartfilter.CategoryUsers({categoryid: categoryid, reporttype: args.reporttype, firstelementactive: FirstElementActive, triggercourseactivities: true });
                    }
                }
            }*/

            //For forms formatting..can't make unique everywhere, so little trick ;)
            $('.filterform' + args.reportid + ' .fitemtitle').hide();
            $('.filterform' + args.reportid + ' .felement').attr('style', 'margin:0');

            $('.basicparamsform' + args.reportid + ' .fitemtitle').hide();
            $('.basicparamsform' + args.reportid + ' .felement').attr('style', 'margin:0');

            /*
             * Filter form submission
             */
            $(".filterform #id_filter_clear").click(function(e) {
                var NumberOfBasicParams = 0;

                $(".filterform" + args.reportid).trigger("reset");
                var activityelement = $(this).parent().find('#id_filter_activity');
                var userelement = $(this).parent().find('#id_filter_users');
                var categoryid = $("#id_filter_coursecategories").find(":selected").val();
                var courseid = $("#id_filter_courses").find(":selected").val();
                var userid = $("#id_filter_users").find(":selected").val();
                // report.CreateReportPage({ reportid: args.reportid, reporttype: args.reporttype, instanceid: args.reportid });
                if (FilterUser.length > 0) {
                    if (FilterCourse.length > 0 || BasicparamCourse.length > 0) {
                        if(BasicparamCourse.length > 0){
                            FirstElementActive = true;
                        }
                        smartfilter.EnrolledUsers({ categoryid: categoryid, courseid: courseid, reporttype: args.reporttype, components: args.components });
                    }
                    $("select[data-select2='1']").select2("destroy").select2({ theme: "classic" });
                }
                if (FilterCourse.length > 0) {
                    if (FilterUser.length > 0 || BasicparamUser.length > 0) {
                        smartfilter.CategoryUsers({ categoryid: categoryid, reporttype: args.reporttype,
                                                 firstelementactive: FirstElementActive, triggercourseactivities: true });
                        
                    } else {
                        smartfilter.categoryCourses({categoryid: categoryid, reporttype: args.reporttype, firstelementactive: FirstElementActive });
                    }                        

                    if (FilterActivity.length > 0 || BasicparamActivity.length > 0) {
                        smartfilter.CourseActivities({ categoryid: categoryid, courseid: courseid });
                    }
                    $("select[data-select2='1']").select2("destroy").select2({ theme: "classic" });
                }
                if (FilterActivity.length > 0) {
                    if (FilterCourse.length > 0 || BasicparamCourse.length > 0) {
                        smartfilter.CourseActivities({ categoryid: categoryid, courseid: courseid });
                        // smartfilter.categoryCourses({categoryid: categoryid, reporttype: args.reporttype, firstelementactive: FirstElementActive });
                    }
                    $("select[data-select2='1']").select2("destroy").select2({ theme: "classic" });
                }
                $("select[data-select2='1']").select2("destroy").select2({ theme: "classic" });
                if ($(".basicparamsform #id_filter_apply").length > 0) {
                    $(document).ajaxComplete(function(event, xhr, settings) {
                        if (settings.url.indexOf("blocks/learnerscript/ajax.php") > 0) {
                            if (typeof settings.data != 'undefined') {
                                var ajaxaction = $.parseJSON(settings.data);
                                if (typeof ajaxaction.basicparam != 'undefined' && ajaxaction.basicparam == true) {
                                    NumberOfBasicParams++;
                                }
                            }
                            if (args.basicparams.length == NumberOfBasicParams) {
                                $(".basicparamsform #id_filter_apply").trigger('click', [true]);
                            }
                        }
                    });
                } else {
                    args.reporttype = $('.ls-plotgraphs_listitem.ui-tabs-active').data('cid');
                    report.CreateReportPage({ reportid: args.reportid, reporttype: args.reporttype, instanceid: args.reportid });
                }
                $(".filterform #id_filter_clear").attr('disabled', 'disabled');
            });

            /*
             * Basic parameters form submission
             */
            $(".basicparamsform #id_filter_apply,.filterform #id_filter_apply").click(function(e, validate) {
                var getreport = helper.validatebasicform(validate);
                e.preventDefault();
                e.stopImmediatePropagation();
                $(".filterform" + args.reportid).show();
                args.instanceid = args.reportid;
                if(e.currentTarget.value != 'Get Report'){
                    $(".filterform #id_filter_clear").removeAttr('disabled');
                }
                if ($.inArray(0, getreport) != -1) {
                    $("#report_plottabs").hide();
                    $("#reportcontainer" + args.reportid).html("<div class='alert alert-info'>No data available</div>");
                } else {
                    $("#report_plottabs").show();
                    report.CreateReportPage({ reportid: args.reportid, reporttype: args.reporttype, instanceid: args.instanceid, reportdashboard: false });
                }
            });
            /*
             * Generate Plotgraph
             */
            if (args.basicparams == null) {
                report.CreateReportPage({ reportid: args.reportid, reporttype: args.reporttype, instanceid: args.reportid, reportdashboard: false });
            } else {
                if (args.basicparams.length == 1) {
                    $(".basicparamsform #id_filter_apply").trigger('click', [true]);
                } else {
                        $(document).ajaxComplete(function(event, xhr, settings) {
                            if (settings.url.indexOf("blocks/learnerscript/ajax.php") > 0) {
                                if (typeof settings.data != 'undefined') {
                                    var ajaxaction = $.parseJSON(settings.data);
                                    if (typeof ajaxaction.basicparam != 'undefined' && ajaxaction.basicparam == true) {
                                        NumberOfBasicParams++;
                                    }
                                }
                                if (args.basicparams.length == NumberOfBasicParams
                                    && ajaxaction.action != 'plotforms' && ajaxaction.action != 'pluginlicence') {
                                    $(".basicparamsform #id_filter_apply").trigger('click', [true]);
                                }
                            }
                        });
                }
            }

            /*
             * Make sure will have vertical tabs for plotoptions for report
             */
            $tabs = $('#report_plottabs').tabs().addClass("ui-tabs-vertical ui-helper-clearfix");
            $("#report_plottabs li").removeClass("ui-corner-top").addClass("ui-corner-left");

            helper.tabsdraggable($tabs);

        },
        CreateReportPage: function(args) {
            if (args.reportdashboard == false) {
                args.reporttype = $('.ls-plotgraphs_listitem.ui-tabs-active').data('cid');
            }
            reportwidget.CreateDashboardwidget({
                reportid: args.reportid,
                reporttype: 'table',
                instanceid: args.reportid,
                reportdashboard: args.reportdashboard
            });
            chart.HighchartsAjax({
                'reportid': args.reportid,
                'action': 'generate_plotgraph',
                'cols': args.cols,
                'reporttype': args.reporttype
            });
        },
        /**
         * Generates graph widget with given Highcharts ajax response
         * @param  object response Ajax response
         * @return Creates highchart widget with given response based on type of chart
         */
        generate_plotgraph: function(response) {
            var returned;
            response.containerid = 'plotreportcontainer' + response.reportinstance;
            switch (response.type) {
                case 'pie':
                    chart.piechart(response);
                    break;
                case 'line':
                case 'bar':
                case 'column':
                    chart.lbchart(response);
                    break;
                case 'solidgauge':
                    chart.solidgauge(response);
                    break;
                case 'combination':
                    chart.combinationchart(response);
                    break;
                case 'map':
                    chart.WorldMap(response);
                    break;
                case 'treemap':
                    chart.TreeMap(response);
                    break;
            }
        },
        /**
         * Datatable serverside for all table type reports
         * @param object args reportid
         * @return Apply serverside datatable to report table
         */
        ReportDatatable: function(args) {
            var self = this;
            var params = {};
            var reportinstance = args.instanceid ? args.instanceid : args.reportid;
            params['filters'] = args.filters;
            params['basicparams'] = args.basicparams || JSON.stringify(smartfilter.BasicparamsData(reportinstance));
            params['reportid'] = args.reportid;
            params['columns'] = args.columns;
            //
            // Pipelining function for DataTables. To be used to the `ajax` option of DataTables
            //
            $.fn.dataTable.pipeline = function(opts) {
                // Configuration options
                var conf = $.extend({
                    url: '', // script url
                    data: null, // function or object with parameters to send to the server
                    method: 'GET' // Ajax HTTP method
                }, opts);

                return function(request, drawCallback, settings) {
                    var ajax = true;
                    var requestStart = request.start;
                    var drawStart = request.start;
                    var requestLength = request.length;
                    var requestEnd = requestStart + requestLength;

                    if (typeof args.data != 'undefined' && request.draw == 1) {
                        json = args.data;
                        json.draw = request.draw; // Update the echo for each response
                        json.data.splice(0, requestStart);
                        json.data.splice(requestLength, json.data.length);
                        drawCallback(json);
                    } else if (ajax) {
                        // Need data from the server
                        request.start = requestStart;
                        request.length = requestLength;
                        $.extend(request, conf.data);

                        settings.jqXHR = $.ajax({
                            "type": conf.method,
                            "url": conf.url,
                            "data": request,
                            "dataType": "json",
                            "cache": false,
                            "success": function(json) {
                                drawCallback(json);
                            }
                        });
                    } else {
                        json = $.extend(true, {}, cacheLastJson);
                        json.draw = request.draw; // Update the echo for each response
                        json.data.splice(0, requestStart - cacheLower);
                        json.data.splice(requestLength, json.data.length);
                        drawCallback(json);
                    }
                }
            };
            if (args.reportname == 'Users profile' || args.reportname == 'Course profile') {
                var lengthoptions = [
                    [50, 100, -1],
                    ["Show 50", "Show 100", "Show All"]
                ];
            } else {
                var lengthoptions = [
                    [10, 25, 50, 100, -1],
                    ["Show 10", "Show 25", "Show 50", "Show 100", "Show All"]
                ];
            }
            var oTable = $('#reporttable_' + reportinstance).DataTable({
                'processing': true,
                'serverSide': true,
                'destroy': true,
                'dom': '<"co_report_header"Bf <"report_header_skew"  <"report_header_skew_content" Bl<"report_header_showhide" ><"report_calculation_showhide" >> > > tr <"co_report_footer"ip>',
                'ajax': $.fn.dataTable.pipeline({
                    "type": "POST",
                    "url": M.cfg.wwwroot + '/blocks/learnerscript/components/datatable/server_processing.php?sesskey=' + M.cfg.sesskey,
                    "data": params
                }),
                'columnDefs': args.columnDefs,
                "fnDrawCallback": function(oSettings, json) {
                    chart.SparkLineReport();
                    helper.DrilldownReport();
                },
                "oScroll": {},
                'responsive': true,
                "fnInitComplete": function() {
                    this.fnAdjustColumnSizing(true);
                    $(".drilldown" + reportinstance + " .ui-dialog-title").html(args.reportname);

                    if (args.reportname == 'Users profile' || args.reportname == 'Course profile') {
                        $("#reporttable_" + reportinstance + "_wrapper .co_report_header").remove();
                        $("#reporttable_" + reportinstance + "_wrapper .co_report_footer").remove();
                    }

                    $('.download_menu' + reportinstance + ' li a').each(function(index) {
                        var link = $(this).attr('href');
                        if (typeof args.basicparams != 'undefined') {
                            var basicparamsdata = JSON.parse(args.basicparams);
                            $.each(basicparamsdata, function(key, value) {
                                if (key.indexOf('filter_') == 0) {
                                    link += '&' + key + '=' + value;
                                }
                            });
                        }
                        if (typeof(args.filters) != 'undefined') {
                            var filters = JSON.parse(args.filters);
                            $.each(filters, function(key, value) {
                                if (key.indexOf('filter_') == 0) {
                                    link += '&' + key + '=' + value;
                                }
                                if(key.indexOf('ls_') == 0) {
                                    link += '&' + key + '=' + value;
                                }
                            });
                        }
                        $(this).attr('href', link);
                    });
                },
                "fnRowCallback": function(nRow, aData, iDisplayIndex) {
                    $(nRow).children().each(function(index, td) {
                        $(td).css("word-break", args.columnDefs[index].wrap);
                        $(td).css("width", args.columnDefs[index].width);
                    });
                    return nRow;
                },
                "autoWidth": false,
                'aaSorting': [],
                'language': {
                    'paginate': {
                        'previous': '<',
                        'next': '>'
                    },
                    'sProcessing': "<img src='" + M.util.image_url('loading', 'block_learnerscript') + "'>",
                    'search': "_INPUT_",
                    'searchPlaceholder': "Search",
                    'lengthMenu': "_MENU_",
                    "emptyTable": "<div class='alert alert-info'>No data available</div>"
                },
                "lengthMenu": lengthoptions
            });

            $("#page-blocks-learnerscript-viewreport #reporttable_" + args.reportid + "_wrapper div.report_header_showhide").
            html($('#export_options' + args.reportid).html());
            if ($('.reportcalculation' + args.reportid).length > 0) {
                $("#page-blocks-learnerscript-viewreport #reporttable_" + args.reportid + "_wrapper div.report_calculation_showhide").
                html('<img src="' + M.util.image_url('calculationicon', 'block_learnerscript') + '" onclick="(function(e){ require(\'block_learnerscript/helper\').reportCalculations({reportid:' + args.reportid + '}) })(event)" title ="Calculations" />');
            }
            $('#export_options' + args.reportid).remove();
        }
    };
});