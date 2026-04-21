from typing import Any

from flask import Blueprint, redirect, render_template, request, url_for

from adms_wrapper.controllers.frontend_logic_controller import (
    # Commented out - used by disabled routes, kept for potential future use
    # handle_designation_mapping_addition,
    # handle_designation_mapping_deletion,
    # handle_device_mapping_addition,
    # handle_device_mapping_deletion,
    # handle_employee_branch_addition,
    # handle_employee_branch_deletion,
    handle_employee_name_addition,
    handle_employee_name_deletion,
    # handle_shift_mapping_addition,
    # handle_shift_mapping_deletion,
)
from adms_wrapper.models.db_queries_models import (
    get_comprehensive_employee_data,
    # Commented out - used by disabled routes, kept for potential future use
    # get_device_branch_mappings,
    # get_employee_branch_mappings,
    # get_employee_designation_mappings,
    get_employee_name_mappings,
    # get_user_shift_mappings,
)

router = Blueprint("mappings", __name__)


# DISABLED: User Shift Mapping route - kept for potential future use
# @router.route("/user_shift_mapping", methods=["GET", "POST"])
# def user_shift_mapping() -> Any:
#     if request.method == "POST":
#         delete_user_id = request.form.get("delete_user_id")
#         if delete_user_id:
#             handle_shift_mapping_deletion(delete_user_id)
#             return redirect(url_for("user_shift_mapping"))
#
#         user_id = request.form.get("user_id")
#         shift_name = request.form.get("shift_name")
#         shift_start = request.form.get("shift_start")
#         shift_end = request.form.get("shift_end")
#
#         handle_shift_mapping_addition(user_id, shift_name, shift_start, shift_end)
#         return redirect(url_for("user_shift_mapping"))
#
#     mappings = get_user_shift_mappings() or []
#     return render_template("user_shift_mapping.html", mappings=mappings)


# DISABLED: Device Branch Mapping route - kept for potential future use
# @router.route("/device_branch_mapping", methods=["GET", "POST"])
# def device_branch_mapping() -> Any:
#     if request.method == "POST":
#         delete_sn = request.form.get("delete_serial")
#         if delete_sn:
#             handle_device_mapping_deletion(delete_sn)
#             return redirect(url_for("device_branch_mapping"))
#
#         serial_number = request.form.get("serial_number")
#         branch_name = request.form.get("branch_name")
#
#         handle_device_mapping_addition(serial_number, branch_name)
#         return redirect(url_for("device_branch_mapping"))
#
#     mappings = get_device_branch_mappings() or []
#     return render_template("device_branch_mapping.html", mappings=mappings)


# DISABLED: Employee Designation Mapping route - kept for potential future use
# @router.route("/employee_designation_mapping", methods=["GET", "POST"])
# def employee_designation_mapping() -> Any:
#     if request.method == "POST":
#         delete_emp_id = request.form.get("delete_employee_id")
#         if delete_emp_id:
#             handle_designation_mapping_deletion(delete_emp_id)
#             return redirect(url_for("employee_designation_mapping"))
#
#         employee_id = request.form.get("employee_id")
#         designation = request.form.get("designation")
#
#         handle_designation_mapping_addition(employee_id, designation)
#         return redirect(url_for("employee_designation_mapping"))
#
#     mappings = get_employee_designation_mappings() or []
#     return render_template("employee_designation_mapping.html", mappings=mappings)


@router.route("/employee_name_mapping", methods=["GET", "POST"])
def employee_name_mapping() -> Any:
    if request.method == "POST":
        delete_emp_id = request.form.get("delete_employee_id")
        if delete_emp_id:
            handle_employee_name_deletion(delete_emp_id)
            return redirect(url_for("employee_name_mapping"))

        employee_id = request.form.get("employee_id")
        employee_name = request.form.get("employee_name")

        handle_employee_name_addition(employee_id, employee_name)
        return redirect(url_for("employee_name_mapping"))

    mappings = get_employee_name_mappings() or []
    # Build a lookup of employee_id -> employee_name from comprehensive employee data
    try:
        comprehensive = get_comprehensive_employee_data() or []
        id_to_name = {str(e.get("employee_id")): e.get("employee_name", "") for e in comprehensive if e.get("employee_id")}
    except Exception:
        id_to_name = {}

    return render_template("employee_name_mapping.html", mappings=mappings, employee_id_to_name=id_to_name)


# DISABLED: Employee Branch Mapping route - kept for potential future use
# @router.route("/employee_branch_mapping", methods=["GET", "POST"])
# def employee_branch_mapping() -> Any:
#     if request.method == "POST":
#         delete_emp_id = request.form.get("delete_employee_id")
#         if delete_emp_id:
#             handle_employee_branch_deletion(delete_emp_id)
#             return redirect(url_for("employee_branch_mapping"))
#
#         employee_id = request.form.get("employee_id")
#         branch_name = request.form.get("branch_name")
#
#         handle_employee_branch_addition(employee_id, branch_name)
#         return redirect(url_for("employee_branch_mapping"))
#
#     mappings = get_employee_branch_mappings() or []
#     all_branches = list({b["branch_name"] for b in get_device_branch_mappings() or []})
#     return render_template("employee_branch_mapping.html", mappings=mappings, all_branches=all_branches)
