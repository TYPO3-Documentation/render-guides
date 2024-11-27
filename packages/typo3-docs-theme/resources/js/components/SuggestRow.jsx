import React, { forwardRef } from 'react';

const SuggestRow = forwardRef(({ title, packageName, scopes, tooltip, onClick, type, href, isActive, icon = 'search' }, ref) => {
    const handleOnClick = (e) => {
        if (!href) {
            e.preventDefault();
            onClick?.();
        }
    };

    return (
        <a
            onClick={handleOnClick}
            ref={ref}
            href={href}
            className={`suggest-row ${isActive ? 'suggest-row--active' : ''}`}
        >
            <div className="suggest-row__icon">
                {icon === 'search' ? <i className="fa fa-search"></i> : icon === 'file' ? <i className="fa-regular fa-file-code"></i> : null}
            </div>
            <div className="suggest-row__content">
                {scopes?.length > 0 ?
                    <>
                        {scopes.map(({ title: scopeName, type }) => (
                            <div className='suggest-row__scope'>
                                <p className="suggest-row__scope-type">{type && `${type}:`}</p>
                                {scopeName && <p className="suggest-row__scope-name">{scopeName}</p>}
                            </div>
                        ))}<p className="suggest-row__title">{title}</p> </> : (
                        <div className='suggest-row__scope' title={`${title}${packageName ? ` (${packageName})` : ''}`}>
                            <p className="suggest-row__scope-type">{type && `${type}:`}</p>
                            <p className="suggest-row__title" dangerouslySetInnerHTML={{ __html: title }}></p>
                        </div>
                    )}
                {packageName && <p className="suggest-row__description">({packageName})</p>}
            </div>
            {tooltip && <p className="suggest-row__tooltip">{tooltip}</p>}
        </a>
    );
});

SuggestRow.displayName = 'SuggestRow';

export default SuggestRow;
