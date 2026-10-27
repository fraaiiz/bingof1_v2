const category = document.querySelector('[data-ticket-category]');
const courseField = document.querySelector('[data-ticket-course]');
const courseSelect = document.querySelector('[data-ticket-course-select]');
const pageUrl = document.querySelector('[data-ticket-page]');

if (category && courseField && courseSelect && pageUrl) {
const originalPage = pageUrl.value;
    const syncCourse = () => {
        const isCourse = category.value === 'course';
        courseField.hidden = !isCourse;
        courseSelect.required = isCourse;
        if (!isCourse) {
            courseSelect.value = '';
            pageUrl.value = originalPage;
            return;
        }

        const selected = courseSelect.selectedOptions[0];
        if (selected && selected.dataset.page) {
            pageUrl.value = selected.dataset.page;
        }
    };

    category.addEventListener('change', syncCourse);
    courseSelect.addEventListener('change', syncCourse);
    syncCourse();
}
