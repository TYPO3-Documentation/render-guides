import React, { forwardRef } from 'react';

const SuggestRow = forwardRef(({ title, packageName, scopeName, tooltip, type, onClick, href, isActive, icon = 'search' }, ref) => {
    return (
        <a
            onClick={(e) => {
                if (!href) {
                    e.preventDefault();
                    onClick?.();
                }
            }}
            ref={ref}
            href={href}
            className={`suggest-row ${isActive ? 'suggest-row--active' : ''}`}
        >
            <div className="suggest-row__icon">
                {icon === 'search' && <i className="fa fa-search"></i>}
                {icon === 'file' && <i className="fa-regular fa-file-code"></i>}
            </div>
            <div className="suggest-row__content">
                <p className="suggest-row__scope">{type && `${type}:`}</p>
                {scopeName && (
                    <p className="suggest-row__scope-name">
                        {scopeName}
                    </p>
                )}
                <p className="suggest-row__title">{title}</p>
                {packageName && (
                    <p className="suggest-row__description">
                        ({packageName})
                    </p>
                )}
            </div>
            {tooltip && <p className="suggest-row__tooltip">{tooltip}</p>}
        </a>
    );
});

SuggestRow.displayName = 'SuggestRow';

export default SuggestRow;
