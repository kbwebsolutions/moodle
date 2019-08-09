define(['jquery',
        'block_learnerscript/ajax',
        'block_learnerscript/reportwidget',
        'block_learnerscript/report',
        'block_learnerscript/jquery.serialize-object'],
    function($, ajax, reportwidget, report) {
        var BasicCoursecategories = $('.filterform #id_filter_coursecategories');
        var BasicparamCourse = $('.basicparamsform #id_filter_courses');
        var BasicparamUser = $('.basicparamsform #id_filter_users');
        var BasicparamActivity = $('.basicparamsform #id_filter_activity');

        var FilterCoursecategories = $('.filterform #id_filter_coursecategories');
        var FilterCourse = $('.filterform #id_filter_courses');
        var FilterUser = $('.filterform #id_filter_users');
        var FilterActivity = $('.filterform #id_filter_activity');

        return smartfilter = {
            DurationFilter: function(value, reportdashboard) {
                var today = new Date();
                var endDate = today.getFullYear() + "/" + (today.getMonth() + 1) + "/" + today.getDate();
                var start_duration = '';
                if (value !== 'clear') {
                    $('#ls_fenddate').val(today.getTime() / 1000);
                    switch (value) {
                        case 'week':
                            start_duration = new Date(today.getFullYear(), today.getMonth(), today.getDate() - 7);
                            break;
                        case 'month':
                            start_duration = new Date(today.getFullYear(), today.getMonth() - 1, today.getDate());
                            break;
                        case 'year':
                            start_duration = new Date(today.getFullYear() - 1, today.getMonth(), today.getDate());
                            break;
                        case 'custom':
                            $('#customrange').show();
                            break;
                        default:
                            break;
                    }
                    if (start_duration != '') {
                        $('#ls_fstartdate').val(start_duration.getTime() / 1000);
                    }
                } else {
                    $('#ls_fenddate').val("");
                    $('#ls_fstartdate').val("");
                }
                if (value !== 'custom') {
                    var reportid = $('input[name="reportid"]').val();
                    if (reportdashboard != false) {
                    	require(['block_learnerscript/reportwidget'], function(reportwidget) {
                        	reportwidget.DashboardTiles();
                        	reportwidget.DashboardWidgets();
                    	});
                    } else {
                    	require(['block_learnerscript/report'], function(report) {
                        	report.CreateReportPage({ reportid: reportid, instanceid: reportid, reportdashboard: reportdashboard });
                        });
                    }
                    $('#customrange').val("");
                    $('#customrange').hide();
                }
            },
            /**
             * [FilterData description]
             * @param {[type]} args [description]
             */
            FilterData: function(reportinstance) {
                var reportfilter = $(".filterform" + reportinstance).serializeObject();
                return reportfilter;
            },
            BasicparamsData: function(reportinstance) {
                var basicparams = $(".basicparamsform" + reportinstance).serializeObject();
                return basicparams;
            },
            CourseData: function(args) {
                var FirstElementActive = false;
                if (BasicparamActivity.length > 0 || FilterActivity.length > 0) {
                    if (BasicparamActivity.length > 0) {
                        FirstElementActive = true;
                    }
                    if (args.courseid > 0 && args.categoryid > 0) {
                        this.CourseActivities({ categoryid: args.categoryid, courseid: args.courseid, firstelementactive: FirstElementActive, activityid: args.filterrequests.filter_activity });
                    }
                }
                if (BasicparamUser.length > 0 || FilterUser.length > 0) {
                    if (BasicparamUser.length > 0) {
                        FirstElementActive = true;
                    }
                    // if (args.courseid > 0) {
                        this.EnrolledUsers({
                            courseid: args.courseid,
                            categoryid: args.categoryid,
                            reporttype: args.reporttype,
                            components: args.components,
                            firstelementactive: FirstElementActive
                        });
                    // }
                }
            },
            categoryCourses: function(args) {
            var currentcategory = $('#id_filter_coursecategories').find(":selected").val();
            if (currentcategory > 0) {
                var promise = ajax.call({
                    args: {
                        action: 'categorycourses',
                        basicparam: true,
                        reporttype: args.reporttype,
                        categoryid: args.categoryid
                    },
                    url: M.cfg.wwwroot + "/blocks/learnerscript/ajax.php",
                });
                var self = this;
                promise.done(function(response) {
                    var template = '';
                    $.each(response, function(key, value) {
                        template += '<option value = ' + key + '>' + value + '</option>';
                    });
                    $("#id_filter_courses").html(template);
                    var currentcourse = $('.basicparamsform #id_filter_courses').find(":selected").val();  
                        if (currentcourse == 0 || currentcourse == null) {
                            $('.basicparamsform #id_filter_courses').val($('.basicparamsform #id_filter_courses option:eq(1)').val());
                        }
                    if (BasicparamActivity.length > 0 || FilterActivity.length > 0) {
                       var currentcourse1 = $('.basicparamsform #id_filter_courses').find(":selected").val();                                    
                       self.CourseActivities({ categoryid: args.categoryid, courseid: currentcourse1 });
                    }
                    if (BasicparamCourse.length > 0 && (FilterUser.length > 0 || BasicparamUser.length > 0) ) {
                        var currentcourse2 = $('.basicparamsform #id_filter_courses').find(":selected").val();       
                        self.EnrolledUsers({ categoryid: args.categoryid, courseid: currentcourse2});
                    }
                    
                });
            }
        }, 
        CategoryUsers: function(args) {
            var currentcategory = $('#id_filter_coursecategories').find(":selected").val();
            if (currentcategory > 0) {
                var promise = ajax.call({
                    args: {
                        action: 'categoryusers',
                        basicparam: true,
                        reporttype: args.reporttype,
                        categoryid: args.categoryid
                    },
                    url: M.cfg.wwwroot + "/blocks/learnerscript/ajax.php",
                });
                var self = this;
                promise.done(function(response) {
                    var template = '';
                    $.each(response, function(key, value) {
                        template += '<option value = ' + key + '>' + value + '</option>';
                    });
                    $("#id_filter_users").html(template);
                    var currentuser = $('.basicparamsform #id_filter_users').find(":selected").val();

                    if (currentuser == 0 || currentuser == null) {
                        $('.basicparamsform #id_filter_users').val($('.basicparamsform #id_filter_users option:eq(1)').val());
                    }
                    var userid = $('#id_filter_users').find(":selected").val(); 
                    if (FilterCourse.length > 0 && (BasicparamUser.length > 0 || FilterUser.length > 0)) {
                        self.UserCourses({ categoryid: currentcategory, userid: userid, reporttype: args.reporttype});
                    }
                });
            }
        },
        CourseActivities: function(args) {
            var nearelement = args.element || $('#id_filter_activity');
            activityid = parseInt(args.activityid) || 0;
            var currentactivity = nearelement.val();
            nearelement.find('option')
                .remove()
                .end()
                .append('<option value=0>Select Activity</option>');
            if (args.courseid >= 0) {
                var promise = ajax.call({
                    args: {
                        action: 'courseactivities',
                        basicparam: true,
                        courseid: args.courseid,
                        categoryid: args.categoryid
                    },
                    url: M.cfg.wwwroot + "/blocks/learnerscript/ajax.php",
                });
                promise.done(function(response) {
                    $.each(response, function(key, value) {
                        key = parseInt(key);
                        if(key == 0){
                            return true;
                        }
                        // (key != currentactivity && key != 0)
                        if (key != activityid) {
                            nearelement.append($("<option></option>")
                                .attr("value", key)
                                .text(value));
                        } else {
                            nearelement.append($("<option></option>")
                                .attr("value", key)
                                .attr('selected', 'selected')
                                .text(value));
                        }
                    });
                    var currentactivity = $('.basicparamsform #id_filter_activity').find(":selected").val();
                    if (currentactivity == 0 || currentactivity == null) {
                        $('.basicparamsform #id_filter_activity').val($('.basicparamsform #id_filter_activity option:eq(1)').val());
                    }
                    var basicparamactivtylen = nearelement.parents('.basicparamsform').length;
                    if (basicparamactivtylen > 0 && args.onloadtrigger) {
                        $(".basicparamsform #id_filter_apply").trigger('click');
                    }
                });
            }
        },
        UserCourses: function(args) {
            var currentcourse = $('#id_filter_courses').find(":selected").val();
            var categoryid = $("#id_filter_coursecategories").find(":selected").val();
            $('#id_filter_courses').find('option')
                .remove()
                .end()
                .append('<option value="">Select Course</option>');
            if (args.userid >= 0) {
                var promise = ajax.call({
                    args: {
                        action: 'usercourses',
                        basicparam: true,
                        userid: args.userid,
                        categoryid: args.categoryid,
                        reporttype: args.reporttype
                    },
                    url: M.cfg.wwwroot + "/blocks/learnerscript/ajax.php",
                });
                promise.done(function(response) {
                    $.each(response, function(key, value) {
                        if(key == 0){
                            return true;
                        }
                        if ((key == Object.keys(response)[0] && args.firstelementactive == 1) ||
                                (key == currentcourse && args.firstelementactive == 1)) {
                            $('#id_filter_courses').append($("<option></option>")
                                .attr("value", key)
                                .attr('selected', 'selected')
                                .text(value));
                            if(typeof args.triggercourseactivities != 'undefined' && args.triggercourseactivities == true){
                                smartfilter.CourseActivities({ categoryid: categoryid , courseid: key });
                            }
                        } else {
                            $('#id_filter_courses').append($("<option></option>")
                                .attr("value", key)
                                .text(value));
                        }
                    });

                });
            }
        },
        EnrolledUsers: function(args) {
            var nearelement = args.element || $('#id_filter_users');
            var currentuser = nearelement.val();
            nearelement.find('option')
                .remove()
                .end()
                .append('<option value=0>Select User</option>');
                var promise = ajax.call({
                    args: {
                        action: 'enrolledusers',
                        basicparam: true,
                        courseid: args.courseid,
                        categoryid: args.categoryid,
                        reporttype: args.reporttype,
                        component: args.components
                    },
                    url: M.cfg.wwwroot + "/blocks/learnerscript/ajax.php",
                });
                promise.done(function(response) {
                    // if (typeof nearelement == 'undefined') {
                    //     nearelement.find('option')
                    //         .not(':eq(0), :selected')
                    //         .remove()
                    //         .end();
                    // }
                    $.each(response, function(key, value) {
                        if(key == 0){
                            return true;
                        }
                        if (key != currentuser) {
                            nearelement.append($("<option></option>")
                                .attr("value", key)
                                .text(value));
                        } else {
                            nearelement.append($("<option></option>")
                                .attr("value", key)
                                .attr('selected', 'selected')
                                .text(value));
                        }
                    });
                    var currentusers = $('.basicparamsform #id_filter_users').find(":selected").val();
                    if (currentusers == 0 || currentusers == null) {

                        $('.basicparamsform #id_filter_users').val($('.basicparamsform #id_filter_users option:eq(1)').val());
                    } else {
                        $('.basicparamsform #id_filter_users').val(currentusers);
                        $(".basicparamsform #id_filter_users option[value='"+currentusers+"']").attr("selected","selected");
                    }
                    // if (!response.hasOwnProperty(currentuser)) {
                    //     nearelement.select2('destroy').select2({ theme: 'classic' });
                    //     nearelement.select2('val', 0);
                    // } else {
                    //     nearelement.select2('val', "");
                    //     var basicparamuserlen = nearelement.parents('.basicparamsform').length;
                    //     if (basicparamuserlen > 0 && args.onloadtrigger) {
                    //         $(".basicparamsform #id_filter_apply").trigger('click');
                    //     }
                    // }
                });

        }
        }
    });