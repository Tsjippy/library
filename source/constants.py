import PySimpleGUI as sg
import logging

logger=logging.getLogger(__name__)
logging.basicConfig(level=logging.DEBUG) # (NOTSET,DEBUG,INFO,WARNING,ERROR,CRITICAL)

db_path             = 'database.sqlite3'
db                  = ''
window              = ''
im_width            = 120
im_height           = 100
icon                = 'iVBORw0KGgoAAAANSUhEUgAAADIAAAAxCAYAAACYq/ofAAAAAXNSR0IArs4c6QAAAARnQU1BAACxjwv8YQUAAAAJcEhZcwAADsMAAA7DAcdvqGQAAAPIaVRYdFhNTDpjb20uYWRvYmUueG1wAAAAAAA8P3hwYWNrZXQgYmVnaW49Iu+7vyIgaWQ9Ilc1TTBNcENlaGlIenJlU3pOVGN6a2M5ZCI/Pg0KPHg6eG1wbWV0YSB4bWxuczp4PSJhZG9iZTpuczptZXRhLyIgeDp4bXB0az0iQWRvYmUgWE1QIENvcmUgNS4zLWMwMTEgNjYuMTQ1NjYxLCAyMDEyLzAyLzA2LTE0OjU2OjI3ICAgICAgICAiPg0KICA8cmRmOlJERiB4bWxuczpyZGY9Imh0dHA6Ly93d3cudzMub3JnLzE5OTkvMDIvMjItcmRmLXN5bnRheC1ucyMiPg0KICAgIDxyZGY6RGVzY3JpcHRpb24gcmRmOmFib3V0PSIiIHhtbG5zOnhtcFJpZ2h0cz0iaHR0cDovL25zLmFkb2JlLmNvbS94YXAvMS4wL3JpZ2h0cy8iIHhtbG5zOnhtcE1NPSJodHRwOi8vbnMuYWRvYmUuY29tL3hhcC8xLjAvbW0vIiB4bWxuczpzdFJlZj0iaHR0cDovL25zLmFkb2JlLmNvbS94YXAvMS4wL3NUeXBlL1Jlc291cmNlUmVmIyIgeG1sbnM6eG1wPSJodHRwOi8vbnMuYWRvYmUuY29tL3hhcC8xLjAvIiB4bXBSaWdodHM6TWFya2VkPSJGYWxzZSIgeG1wTU06T3JpZ2luYWxEb2N1bWVudElEPSJ1dWlkOjQ5REI4Q0IxOUFERkRBMTE5NzQxRDcxN0VFMDkxRTBFIiB4bXBNTTpEb2N1bWVudElEPSJ4bXAuZGlkOjQyQTg5MTE3RDhEMjExRTY5RTRBRTIyRTlGOEJBOTQyIiB4bXBNTTpJbnN0YW5jZUlEPSJ4bXAuaWlkOjQyQTg5MTE2RDhEMjExRTY5RTRBRTIyRTlGOEJBOTQyIiB4bXA6Q3JlYXRvclRvb2w9IkFkb2JlIFBob3Rvc2hvcCBDUzUgV2luZG93cyI+DQogICAgICA8eG1wTU06RGVyaXZlZEZyb20gc3RSZWY6aW5zdGFuY2VJRD0ieG1wLmlpZDpGNUFENEMw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'
loaned_items_data   = []
label_width         = 10
input_width         = 30
current_user_data   = ''
current_item_data   = ''
current_author_data = ''
current_tab         = 'Check out'
settings            = ''
userfields          = ["id","display_name", "first_name", "last_name", "birthday", "barcode", "picture", "max_items", "loan_period"]
itemfields          = ["id","title", "item_type", "author", "call_number", "isbn", "barcode", "picture", "linked_to", "loaned_since", "due_date"]
ignore_close        = False
refresh             = False

def set_theme():
    if 'theme' in settings:
        theme_name  = settings['theme']
    else:
        theme_name          = 'DarkBlue3'
    sg.theme(theme_name)