from datetime import datetime, timedelta
from typing import Any

from flask import Blueprint, render_template, request, url_for

from adms_wrapper.controllers.frontend_logic_controller import (
    add_branch_info_to_summary,
    add_employee_name_to_summary,
    prepare_dashboard_summary,
)
from adms_wrapper.models.db_queries_models import (
    get_attendences_filtered,
    get_attendences_filtered_count,
    get_device_branch_mappings,
    get_employee_branch_mappings,
    get_employee_designation_mappings,
    get_employee_name_mappings,
    get_user_shift_mappings,
)

router = Blueprint("dashboard", __name__)


@router.route("/", methods=["GET"])
def index() -> Any:
    # Check if user wants to load data
    load_data = request.args.get("load_data", "false").lower() == "true"

    # Pagination: how many items to show (default 20). "Show 20 more" will increase this.
    try:
        limit = int(request.args.get("limit", 20))
    except Exception:
        limit = 20
    # Guardrails for limit
    if limit < 1:
        limit = 20
    limit = min(limit, 2000)

    start_date = request.args.get("start_date")
    end_date = request.args.get("end_date")
    employee_id = request.args.get("employee_id")
    branch_name = request.args.get("branch_name")
    employee_branch = request.args.get("employee_branch")
    employee_name = request.args.get("employee_name")
    designation = request.args.get("designation")

    # Initialize empty data containers
    filtered_attendences_full = []
    attendences = []
    total_attendences = 0
    summary = []
    total_summary = 0
    has_more_summary = False
    has_more_attendences = False
    show_more_url = "#"
    all_employees = []

    # Always load these for the dropdowns
    all_branches = list({b["branch_name"] for b in get_device_branch_mappings() or []})
    all_employee_branches = list({eb["branch_name"] for eb in get_employee_branch_mappings() or []})
    employee_names = get_employee_name_mappings() or []
    employee_designations = get_employee_designation_mappings() or []
    all_employee_names = sorted([name["employee_name"] for name in employee_names if name.get("employee_name")])
    all_designations = sorted(list({des["designation"] for des in employee_designations if des.get("designation")}))
    shift_mappings = get_user_shift_mappings() or []

    # Only load data if explicitly requested
    if load_data:
        # If no date range provided, default to last 30 days to avoid full-table scans
        if not start_date and not end_date:
            today = datetime.utcnow().date()
            end_date = today.strftime("%Y-%m-%d")
            start_date = (today - timedelta(days=30)).strftime("%Y-%m-%d")

        # SQL-side filtered data: get a limited slice for raw table and a full set for summary
        filtered_attendences_full = get_attendences_filtered(start_date, end_date, employee_id, branch_name, employee_branch, employee_name, designation) or []
        attendences = get_attendences_filtered(start_date, end_date, employee_id, branch_name, employee_branch, employee_name, designation, limit=limit, offset=None) or []
        total_attendences = get_attendences_filtered_count(start_date, end_date, employee_id, branch_name, employee_branch, employee_name, designation)

        # Build full summary (already constrained by SQL filters), then sort and paginate; enrich only visible rows
        summary_full = prepare_dashboard_summary(filtered_attendences_full, shift_mappings, start_date, end_date, branch_name, employee_branch, employee_name, designation)
        summary_full = sorted(summary_full, key=lambda x: (x.get("shift_name") or "", x.get("employee_id") or "", x.get("day") or ""))
        total_summary = len(summary_full)
        summary = summary_full[:limit]
        # Enrich only visible rows
        add_branch_info_to_summary(summary)
        add_employee_name_to_summary(summary)

        # Determine if there's more to show and construct a URL that preserves filters
        has_more_summary = total_summary > limit
        has_more_attendences = (total_attendences or 0) > len(attendences)
        next_limit = limit + 20
        # Build show-more URL with current filters + increased limit
        try:
            args_dict = request.args.to_dict(flat=True)
        except Exception:
            args_dict = {}
        args_dict["limit"] = str(next_limit)
        show_more_url = url_for("dashboard.index", **args_dict)

        # Use the filtered set to derive employee options; cap to avoid rendering massive dropdowns
        all_employees = sorted({str(a.get("employee_id", "")) for a in filtered_attendences_full if a.get("employee_id")})
        MAX_EMPLOYEE_OPTIONS = 500
        if len(all_employees) > MAX_EMPLOYEE_OPTIONS:
            all_employees = all_employees[:MAX_EMPLOYEE_OPTIONS]
    else:
        # Set default date range for display purposes only
        if not start_date and not end_date:
            today = datetime.utcnow().date()
            end_date = today.strftime("%Y-%m-%d")
            start_date = (today - timedelta(days=30)).strftime("%Y-%m-%d")

    return render_template(
        "dashboard.html",
        attendences=attendences,
        summary=summary,
        limit=limit,
        has_more_summary=has_more_summary,
        has_more_attendences=has_more_attendences,
        show_more_url=show_more_url,
        start_date=start_date or "",
        end_date=end_date or "",
        employee_id=employee_id or "",
        branch_name=branch_name or "",
        employee_branch=employee_branch or "",
        employee_name=employee_name or "",
        designation=designation or "",
        all_employees=sorted(all_employees),
        all_branches=sorted(all_branches),
        all_employee_branches=sorted(all_employee_branches),
        all_employee_names=all_employee_names,
        all_designations=all_designations,
        shift_mappings=shift_mappings,
        data_loaded=load_data,
    )
